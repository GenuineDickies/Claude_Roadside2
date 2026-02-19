# SMS Message Templates

Pre-approved customer-facing message templates for dispatch workflow notifications.

Use `[Brand Name]` as the customer-visible business identifier (set by `sms_brand_name` in settings).

## Template Requirements

All templates must include:
- ✅ Business name identifier
- ✅ Opt-out instructions (`Reply STOP to opt out`)
- ✅ Clear, concise content
- ✅ No prohibited content (SHAFT-C)
- ✅ Accurate information only

---

## Workflow Templates

### 0. Opt-In Confirmation (Customer Care)

**Trigger**: After capturing verbal opt-in (or web-form opt-in) for service updates

```
[Brand Name]: You're opted in to receive service updates by text.
Message frequency varies. Msg & data rates may apply.
Reply STOP to opt out, HELP for help.
```

**Notes**:
- Use for customer-care / transactional messaging only (not marketing)
- Consider sending immediately after consent is captured for a clean audit trail

### 1. Ticket Created / Service Confirmed

**Trigger**: Immediately after service request is created

```
[Brand Name]: Service confirmed! Ticket #{TICKET_ID}. 
{SERVICE_TYPE} at {LOCATION}.
We're dispatching help now. Reply STOP to opt out.
```

**Variables**:
- `{TICKET_ID}` — Service ticket number
- `{SERVICE_TYPE}` — e.g., "Flat tire repair", "Jump start"
- `{LOCATION}` — Service location address

**Example**:
```
[Brand Name]: Service confirmed! Ticket #SR-2026-0142.
Flat tire repair at 1234 Main St, Portland OR.
We're dispatching help now. Reply STOP to opt out.
```

---

### 2. Technician Dispatched

**Trigger**: When technician is assigned and en route

```
[Brand Name]: {TECH_NAME} is on the way!
Vehicle: {VEHICLE_DESC}
ETA: {ETA_MINUTES} minutes
Track: {TRACKING_LINK}
Reply STOP to opt out.
```

**Variables**:
- `{TECH_NAME}` — Technician first name
- `{VEHICLE_DESC}` — e.g., "Gray Ford F-150"
- `{ETA_MINUTES}` — Estimated minutes
- `{TRACKING_LINK}` — Optional tracking URL

**Example**:
```
[Brand Name]: John is on the way!
Vehicle: Gray Ford F-150
ETA: 15 minutes
Reply STOP to opt out.
```

---

### 3. ETA Update

**Trigger**: When ETA changes by 10+ minutes

```
[Brand Name] Update: New ETA {ETA_TIME}.
{REASON}
Your technician {TECH_NAME} will arrive soon.
Reply STOP to opt out.
```

**Variables**:
- `{ETA_TIME}` — New arrival time
- `{REASON}` — Brief explanation (traffic, prior job)
- `{TECH_NAME}` — Technician name

**Example**:
```
[Brand Name] Update: New ETA 3:45 PM.
Traffic delay on I-84.
Your technician John will arrive soon.
Reply STOP to opt out.
```

---

### 4. Technician Arrived

**Trigger**: GPS-triggered when tech arrives at location

```
[Brand Name]: {TECH_NAME} has arrived at your location.
Please look for: {VEHICLE_DESC}
Questions? Call {SUPPORT_PHONE}
Reply STOP to opt out.
```

**Example**:
```
[Brand Name]: John has arrived at your location.
Please look for: Gray Ford F-150
Questions? Call [Support Phone]
Reply STOP to opt out.
```

---

### 5. Service Complete

**Trigger**: When work order is marked complete

```
[Brand Name]: Service complete!
{SERVICE_SUMMARY}
Total: ${AMOUNT}
Receipt: {RECEIPT_LINK}
Thank you for choosing [Brand Name]!
Reply STOP to opt out.
```

**Variables**:
- `{SERVICE_SUMMARY}` — Brief description
- `{AMOUNT}` — Total charged
- `{RECEIPT_LINK}` — Link to digital receipt

**Example**:
```
[Brand Name]: Service complete!
Flat tire replaced with spare.
Total: $95.00
Receipt: https://example.com/receipt/12345
Thank you for choosing [Brand Name]!
Reply STOP to opt out.
```

---

### 6. Payment Confirmation

**Trigger**: After successful payment processing

```
[Brand Name] Payment: ${AMOUNT} received.
Invoice #{INVOICE_ID}
Receipt sent to {EMAIL}
Thank you!
Reply STOP to opt out.
```

**Example**:
```
[Brand Name] Payment: $95.00 received.
Invoice #INV-2026-0089
Receipt sent to john@email.com
Thank you!
Reply STOP to opt out.
```

---

### 7. Follow-Up Survey (24 hours later)

**Trigger**: 24 hours after service completion  
**Note**: Requires explicit consent (separate from transactional)

```
[Brand Name]: How was your service yesterday?
Rate us 1-5: {SURVEY_LINK}
Your feedback helps us improve!
Reply STOP to opt out.
```

**Example**:
```
[Brand Name]: How was your service yesterday?
Rate us 1-5: https://example.com/survey/12345
Your feedback helps us improve!
Reply STOP to opt out.
```

---

## Auto-Reply Templates

### HELP Response

**Trigger**: Customer replies "HELP"

```
[Brand Name]
Support: [Support Phone]
Email: [Support Email]
Hours: [Support Hours]
Reply STOP to unsubscribe.
```

---

### STOP Response

**Trigger**: Customer replies "STOP"

```
[Brand Name]: You've been unsubscribed and will no longer receive messages. Reply START to resubscribe.
```

---

### START Response

**Trigger**: Customer replies "START" after opting out

```
[Brand Name]: Welcome back! You've been resubscribed to service notifications. Reply STOP to opt out.
```

---

## PHP Template Function

```php
<?php
/**
 * SMS Message Templates
 */
class SMSTemplates {
    
    const TEMPLATES = [
        'ticket_created' => "{brand_name}: Service confirmed! Ticket #{ticket_id}.\n{service_type} at {location}.\nWe're dispatching help now. Reply STOP to opt out.",
        
        'tech_dispatched' => "{brand_name}: {tech_name} is on the way!\nVehicle: {vehicle_desc}\nETA: {eta_minutes} minutes\nReply STOP to opt out.",
        
        'eta_update' => "{brand_name} Update: New ETA {eta_time}.\n{reason}\nYour technician {tech_name} will arrive soon.\nReply STOP to opt out.",
        
        'tech_arrived' => "{brand_name}: {tech_name} has arrived at your location.\nPlease look for: {vehicle_desc}\nQuestions? Call {support_phone}\nReply STOP to opt out.",
        
        'service_complete' => "{brand_name}: Service complete!\n{service_summary}\nTotal: \${amount}\nThank you for choosing {brand_name}!\nReply STOP to opt out.",
        
        'payment_received' => "{brand_name} Payment: \${amount} received.\nInvoice #{invoice_id}\nThank you!\nReply STOP to opt out.",
        
        'help_response' => "{brand_name}\nSupport: {support_phone}\nEmail: {support_email}\nHours: {support_hours}\nReply STOP to unsubscribe.",
        
        'stop_response' => "{brand_name}: You've been unsubscribed and will no longer receive messages. Reply START to resubscribe.",
        
        'start_response' => "{brand_name}: Welcome back! You've been resubscribed to service notifications. Reply STOP to opt out."
    ];
    
    /**
     * Get a rendered template
     * 
     * @param string $template_name Template key
     * @param array $variables Key-value pairs to replace
     * @return string Rendered message
     */
    public static function render($template_name, $variables = []) {
        if (!isset(self::TEMPLATES[$template_name])) {
            throw new InvalidArgumentException("Unknown template: $template_name");
        }
        
        $message = self::TEMPLATES[$template_name];
        
        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        return $message;
    }
    
    /**
     * Send template message via Telnyx
     * 
     * @param string $to Recipient phone
     * @param string $template_name Template key
     * @param array $variables Template variables
     * @return array API response
     */
    public static function send($to, $template_name, $variables = []) {
        $message = self::render($template_name, $variables);
        return send_sms($to, $message);
    }
}

// Usage examples:

// Ticket created
SMSTemplates::send('+15551234567', 'ticket_created', [
    'ticket_id' => 'SR-2026-0142',
    'service_type' => 'Flat tire repair',
    'location' => '1234 Main St, Portland OR'
]);

// Technician dispatched
SMSTemplates::send('+15551234567', 'tech_dispatched', [
    'tech_name' => 'John',
    'vehicle_desc' => 'Gray Ford F-150',
    'eta_minutes' => '15'
]);

// Service complete
SMSTemplates::send('+15551234567', 'service_complete', [
    'service_summary' => 'Flat tire replaced with spare',
    'amount' => '95.00'
]);
```

---

## Character Counts

Keep messages under 160 characters when possible (single segment = lower cost):

| Template | Approx Length | Segments |
|----------|---------------|----------|
| ticket_created | 120-140 chars | 1 |
| tech_dispatched | 90-110 chars | 1 |
| eta_update | 100-130 chars | 1 |
| tech_arrived | 110-140 chars | 1 |
| service_complete | 130-160 chars | 1 |
| payment_received | 90-110 chars | 1 |

---

## Compliance Checklist

Before sending any template:

- [ ] Includes the correct business identifier (use settings-driven `[Brand Name]`)
- [ ] Includes "Reply STOP to opt out"
- [ ] No SHAFT-C content
- [ ] No misleading information
- [ ] Customer has SMS consent on file
- [ ] Message relates to active service request
