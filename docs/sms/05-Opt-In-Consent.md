# SMS Opt-In and Consent Management

Complete guide to TCPA compliance, consent collection, and opt-out handling.

## Legal Framework

### TCPA (Telephone Consumer Protection Act)

The TCPA requires **prior express consent** before sending:
- Marketing/promotional messages
- Non-emergency automated messages

### 10DLC Requirements

Carriers require proof of opt-in for:
- All A2P (application-to-person) messaging
- Documented consent with timestamp and method

### Penalties

| Violation | Fine |
|-----------|------|
| TCPA per-message | $500-$1,500 |
| T-Mobile content violation | $10,000 |
| FCC enforcement | Up to $10,000 per violation |

---

## Types of Consent

### Express Consent (Transactional)

For **service-related messages**:
- Service confirmations
- Dispatch notifications
- ETA updates
- Payment receipts

**How to obtain**: Verbal confirmation during service call

### Express Written Consent (Marketing)

For **promotional messages**:
- Surveys
- Promotional offers
- Marketing campaigns

**How to obtain**: Written/digital signature or checkbox

---

## Collecting Consent

### During Dispatch Call (Verbal)

**Script for dispatchers**:

> "To keep you updated on your service, we'd like to text you at [phone number] with dispatch info, ETA updates, and completion notifications. Message frequency varies. Msg & data rates may apply. Reply STOP to opt out and HELP for help. These are service messages only, not marketing, and consent is not required to receive service. Do we have your permission to text you at this number?"

**If YES**:
1. Check SMS consent box in intake form
2. System records consent with:
   - Phone number
   - Timestamp
   - Method: "verbal-dispatcher"
   - Dispatcher ID

**If NO**:
1. Leave SMS consent unchecked
2. Note: "Customer declined SMS"
3. Use voice calls only for updates

---

### Via Web Form

Include this language with checkbox:

**Customer-facing text (plain text)**:

```
I agree to receive customer care text messages from [Brand Name] regarding my service request (dispatch notifications, ETA updates, and completion confirmations). Message frequency varies. Msg & data rates may apply. Reply STOP to opt out and HELP for help. Consent is not required to receive service.
```

**Implementation example (HTML)**:

> Note: In HTML, use `&amp;` to display an ampersand (`&`).

```html
<label class="form-check">
    <input type="checkbox" name="sms_consent" id="sms_consent" value="1">
    <span class="form-check-label">
        I agree to receive customer care text messages from [Brand Name]
        regarding my service request (dispatch notifications, ETA updates,
        and completion confirmations). Message frequency varies.
        Msg &amp; data rates may apply. Reply STOP to opt out and HELP for help.
        Consent is not required to receive service.
    </span>
</label>
```

---

### Via SMS Keyword (START)

For re-subscribing after opt-out:

1. Customer texts "START" to your number
2. System records consent with method: "sms-keyword"
3. Send confirmation:

```
[Brand Name]: You've subscribed to service notifications. 
Reply STOP to unsubscribe. Msg & data rates may apply.
```

---

## Consent Database Schema

```sql
CREATE TABLE sms_consent (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    phone_number VARCHAR(20) NOT NULL,
    
    -- Consent status
    consented TINYINT(1) DEFAULT 0,
    consent_type ENUM('transactional', 'marketing') DEFAULT 'transactional',
    
    -- How consent was obtained
    consent_method ENUM(
        'verbal-dispatcher',
        'web-form', 
        'sms-keyword',
        'paper-form'
    ) NOT NULL,
    
    -- Audit trail
    consented_at TIMESTAMP NULL,
    consented_by VARCHAR(100),  -- Dispatcher name/ID
    consent_ip VARCHAR(45),     -- For web forms
    
    -- Opt-out tracking
    opted_out TINYINT(1) DEFAULT 0,
    opted_out_at TIMESTAMP NULL,
    opt_out_method ENUM('sms-keyword', 'web', 'phone', 'email') NULL,
    
    -- Re-subscribe tracking
    resubscribed_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_phone (phone_number),
    INDEX idx_customer (customer_id),
    INDEX idx_consent_status (consented, opted_out)
);
```

---

## PHP Consent Functions

```php
<?php
/**
 * SMS Consent Management
 */

/**
 * Record SMS consent
 */
function record_sms_consent($phone, $method, $consented_by = null, $consent_ip = null) {
    global $pdo;
    
    $phone = format_e164($phone);
    
    $stmt = $pdo->prepare("
        INSERT INTO sms_consent 
            (phone_number, consented, consent_method, consented_at, consented_by, consent_ip)
        VALUES 
            (?, 1, ?, NOW(), ?, ?)
        ON DUPLICATE KEY UPDATE
            consented = 1,
            consent_method = VALUES(consent_method),
            consented_at = NOW(),
            consented_by = VALUES(consented_by),
            consent_ip = VALUES(consent_ip),
            opted_out = 0,
            opted_out_at = NULL
    ");
    
    return $stmt->execute([$phone, $method, $consented_by, $consent_ip]);
}

/**
 * Check if phone has SMS consent
 */
function has_sms_consent($phone) {
    global $pdo;
    
    $phone = format_e164($phone);
    
    $stmt = $pdo->prepare("
        SELECT consented, opted_out 
        FROM sms_consent 
        WHERE phone_number = ?
    ");
    $stmt->execute([$phone]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        return false;
    }
    
    return $row['consented'] == 1 && $row['opted_out'] == 0;
}

/**
 * Record opt-out
 */
function record_opt_out($phone, $method = 'sms-keyword') {
    global $pdo;
    
    $phone = format_e164($phone);
    
    $stmt = $pdo->prepare("
        UPDATE sms_consent 
        SET opted_out = 1, 
            opted_out_at = NOW(),
            opt_out_method = ?
        WHERE phone_number = ?
    ");
    
    return $stmt->execute([$method, $phone]);
}

/**
 * Record re-subscribe
 */
function record_resubscribe($phone) {
    global $pdo;
    
    $phone = format_e164($phone);
    
    $stmt = $pdo->prepare("
        UPDATE sms_consent 
        SET opted_out = 0, 
            opted_out_at = NULL,
            resubscribed_at = NOW()
        WHERE phone_number = ?
    ");
    
    return $stmt->execute([$phone]);
}

/**
 * Safe send - only sends if consent exists
 */
function send_sms_with_consent_check($phone, $message) {
    if (!has_sms_consent($phone)) {
        error_log("SMS blocked - no consent: $phone");
        return [
            'success' => false,
            'error' => 'No SMS consent on file',
            'blocked' => true
        ];
    }
    
    return send_sms($phone, $message);
}
```

---

## Opt-Out Keywords

Telnyx automatically handles these keywords when configured:

| Keyword | Action |
|---------|--------|
| STOP | Opt-out |
| UNSUBSCRIBE | Opt-out |
| CANCEL | Opt-out |
| END | Opt-out |
| QUIT | Opt-out |
| HELP | Send help message |
| INFO | Send help message |
| START | Opt back in |

### Handling in Webhook

```php
<?php
function handle_inbound_message($data) {
    $from = $data['from']['phone_number'];
    $text = strtoupper(trim($data['text'] ?? ''));
    
    // Opt-out keywords
    $opt_out_keywords = ['STOP', 'UNSUBSCRIBE', 'CANCEL', 'END', 'QUIT'];
    if (in_array($text, $opt_out_keywords)) {
        record_opt_out($from, 'sms-keyword');
        
        // Telnyx sends automatic STOP response, but you can customize
        send_sms($from, "[Brand Name]: You've been unsubscribed. Reply START to resubscribe.");
        
        log_action('sms_opt_out', ['phone' => $from]);
        return;
    }
    
    // Help keywords
    $help_keywords = ['HELP', 'INFO'];
    if (in_array($text, $help_keywords)) {
        send_sms($from, "[Brand Name]\nSupport: [Support Phone]\nReply STOP to unsubscribe.");
        return;
    }
    
    // Re-subscribe
    if ($text === 'START') {
        record_resubscribe($from);
        send_sms($from, "[Brand Name]: You're resubscribed! Reply STOP to opt out.");
        return;
    }
    
    // Regular message - process normally
    process_customer_reply($from, $data['text']);
}
```

---

## Intake Form Integration

### HTML for Service Intake Form

```html
<div class="intake-sms-consent mb-3">
    <label class="form-label fw-semibold">SMS Notifications</label>
    
    <div class="form-check">
        <input type="checkbox" 
               class="form-check-input" 
               id="sms_consent" 
               name="sms_consent" 
               value="1">
        <label class="form-check-label" for="sms_consent">
            Customer consents to receive SMS updates about this service
        </label>
    </div>
    
    <small class="text-muted d-block mt-1">
        Read to customer: "May we text you updates about your service 
        including when the technician is on the way?"
    </small>
</div>
```

### PHP Processing

```php
<?php
// In service intake form handler

$sms_consent = isset($_POST['sms_consent']) && $_POST['sms_consent'] == '1';

if ($sms_consent) {
    record_sms_consent(
        $customer_phone,
        'verbal-dispatcher',
        $_SESSION['dispatcher_name'] ?? 'System',
        null
    );
}

// Save with ticket
$stmt = $pdo->prepare("
    INSERT INTO service_requests (customer_phone, sms_consent, ...)
    VALUES (?, ?, ...)
");
$stmt->execute([$customer_phone, $sms_consent ? 1 : 0, ...]);
```

---

## Audit Trail

For compliance, maintain records of:

| Data Point | Retention |
|------------|-----------|
| Consent timestamp | 5+ years |
| Consent method | 5+ years |
| Who obtained consent | 5+ years |
| Opt-out timestamp | 5+ years |
| All sent messages | 2+ years |

### Query Example

```sql
-- Get consent history for a phone number
SELECT 
    phone_number,
    consent_method,
    consented_at,
    consented_by,
    opted_out,
    opted_out_at,
    opt_out_method,
    resubscribed_at
FROM sms_consent 
WHERE phone_number = '+15551234567';
```

---

## Compliance Checklist

Before sending any SMS:

- [ ] Phone number has consent on file
- [ ] Consent record is documented with method and timestamp
- [ ] Customer has not opted out
- [ ] Message includes opt-out instructions
- [ ] Message is relevant to the service/consent type

**Note**: While there's no fixed consent expiration in TCPA, best practice is to re-confirm marketing consent periodically (e.g., annually) and always verify transactional messages are tied to active service requests.

---

## Quick Links

- [FCC TCPA Rules](https://www.fcc.gov/consumers/guides/stop-unwanted-robocalls-and-texts)
- [CTIA Messaging Principles](https://www.ctia.org/the-wireless-industry/industry-commitments/messaging-principles-and-best-practices)
- [Telnyx 10DLC Compliance](https://support.telnyx.com/en/articles/5664840-telnyx-10dlc-compliance)
