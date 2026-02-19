# 10DLC Compliance Guide

> **CRITICAL**: As of February 3, 2025, unregistered 10DLC traffic is BLOCKED entirely by carriers.

## What is 10DLC?

10DLC (10-Digit Long Code) is the industry standard for Application-to-Person (A2P) messaging in the United States. It requires businesses to register their:

1. **Brand** — Your business identity (linked to EIN)
2. **Campaign** — Your messaging use case
3. **Phone Numbers** — Associated with campaigns

## Why 10DLC Matters

| Without 10DLC | With 10DLC |
|---------------|------------|
| Messages blocked entirely | Full deliverability |
| $0.011/SMS carrier fees | $0.003/SMS carrier fees |
| Potential $10,000+ fines | Compliant operation |
| 75 TPM throughput | Up to 4,500 TPM |

---

## Registration Hierarchy

```
Brand (1 per EIN)
├── Campaign 1 (up to 5 campaigns per brand)
│   ├── Phone Number 1
│   ├── Phone Number 2
│   └── ... (max 49 per campaign on T-Mobile)
├── Campaign 2
│   └── Phone Number 3
└── Campaign 3
    └── Phone Number 4
```

---

## Step 1: Create a Brand

Brands identify your business in the carrier ecosystem.

### Via Portal
1. Navigate to [Brands](https://portal.telnyx.com/#/messaging-10dlc/brands)
2. Click **Create Brand**
3. Enter business information:
   - Legal company name
   - EIN (Employer Identification Number)
   - Business address
   - Contact information
4. Click **Save**

### Via API
```bash
curl -X POST https://api.telnyx.com/v2/10dlc/brands \
  -H "Authorization: Bearer $TELNYX_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "entityType": "PRIVATE_PROFIT",
    "displayName": "[Brand Name]",
    "companyName": "[Legal Company Name]",
    "ein": "XX-XXXXXXX",
    "email": "[Support Email]",
    "phone": "[Support Phone E.164]",
    "street": "123 Main St",
    "city": "Portland",
    "state": "OR",
    "postalCode": "97201",
    "country": "US",
    "vertical": "TRANSPORTATION"
  }'
```

### Entity Types
| Type | Description |
|------|-------------|
| `PRIVATE_PROFIT` | Standard for-profit business |
| `PUBLIC_PROFIT` | Publicly traded company |
| `NONPROFIT` | 501(c)(3) organization |
| `GOVERNMENT` | Government agency |
| `SOLE_PROPRIETOR` | Individual without EIN |

---

## Step 2: Vet Your Brand (Recommended)

Vetting improves your Trust Score, increasing throughput limits.

### Why Vet?
- **Unvetted brands**: Low throughput (2,000-10,000 daily T-Mobile limit)
- **Vetted brands**: High throughput (up to 200,000 daily T-Mobile limit)

### Vetting Process
1. Go to [Brands](https://portal.telnyx.com/#/messaging-10dlc/brands)
2. Click on your brand
3. Under **Vetting Request**, select:
   - Provider: **Aegis Mobile**
   - Vetting Class: **Standard**
4. Click **Apply for Vetting**

**Cost**: One-time vetting fee charged by third-party

---

## Step 3: Create a Campaign

Campaigns define your messaging use case.

### Campaign Types

| Use Case | Description | Monthly Fee |
|----------|-------------|-------------|
| `CUSTOMER_CARE` | Support/service messages | $10/mo |
| `DELIVERY_NOTIFICATIONS` | Shipment/service status | $10/mo |
| `ACCOUNT_NOTIFICATIONS` | Account alerts | $10/mo |
| `2FA` | Two-factor authentication | $10/mo |
| `MARKETING` | Promotional content | $10/mo |
| `MIXED` | Multiple use cases | $10/mo |
| `LOW_VOLUME_MIXED` | <6000 segments/month | $1.50/mo |
| `CHARITY` | Nonprofit messaging | $3/mo |
| `EMERGENCY` | Emergency alerts | $5/mo |

### Recommended for Dispatch Operations

Use **`DELIVERY_NOTIFICATIONS`** or **`CUSTOMER_CARE`** for dispatch operations:

```bash
curl -X POST https://api.telnyx.com/v2/10dlc/campaignBuilders \
  -H "Authorization: Bearer $TELNYX_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "brandId": "YOUR_BRAND_ID",
    "usecase": "DELIVERY_NOTIFICATIONS",
    "description": "Roadside assistance service notifications including technician dispatch, ETA updates, and service completion confirmations.",
    "messageFlow": "Customers request roadside assistance via phone or web. Upon dispatching a technician, they receive confirmation, ETA updates, and completion notifications.",
    "helpMessage": "Reply HELP for assistance. Call 1-800-XXX-XXXX for support.",
    "optoutMessage": "Reply STOP to unsubscribe from service notifications.",
    "sample1": "[Brand Name]: Your technician John is on the way. ETA 15 minutes. Gray Ford F-150. Reply STOP to opt out.",
    "sample2": "[Brand Name]: Service complete! Total: $125.00. Reply STOP to opt out.",
    "embeddedLink": false,
    "embeddedPhone": true,
    "numberPool": false,
    "directLending": false,
    "subscriberOptin": true,
    "subscriberOptout": true,
    "subscriberHelp": true,
    "ageGated": false,
    "termsAndConditions": true
  }'
```

### Required Campaign Attributes

| Field | Description | Required |
|-------|-------------|----------|
| `description` | Detailed use case explanation | ✅ |
| `messageFlow` | How customers opt in | ✅ |
| `helpMessage` | Response to HELP keyword | ✅ |
| `optoutMessage` | Response to STOP keyword | ✅ |
| `sample1`, `sample2` | Example messages | ✅ |
| `subscriberOptin` | Supports opt-in | ✅ |
| `subscriberOptout` | Supports opt-out | ✅ |
| `subscriberHelp` | Supports HELP | ✅ |

---

## Step 4: Assign Phone Numbers

```bash
curl -X POST https://api.telnyx.com/v2/10dlc/phone_number_campaigns \
  -H "Authorization: Bearer $TELNYX_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "phoneNumber": "+15551234567",
    "campaignId": "YOUR_CAMPAIGN_ID"
  }'
```

### Limits
- **T-Mobile**: Max 49 numbers per campaign
- **Daily limit**: Based on brand tier (see throughput section)

---

## Throughput Limits

### T-Mobile Daily Limits (by Brand Tier)

| Brand Tier | Vetting Score | Daily Cap |
|------------|---------------|-----------|
| Top | 75-100 | 200,000 |
| High Mid | 50-74 | 40,000 |
| Low Mid | 25-49 | 10,000 |
| Low | 1-24 | 2,000 |

### AT&T Throughput (by Message Class)

| Class | Vetting Score | TPM (SMS) | TPM (MMS) |
|-------|---------------|-----------|-----------|
| A, B | 75-100 | 4,500 | 2,400 |
| C, D | 50-74 | 2,400 | 1,200 |
| E, F | 1-49 | 240 | 150 |
| T (Unregistered) | N/A | 75 | 50 |

---

## Non-Compliance Fines

⚠️ **These are carrier-imposed fines, not Telnyx fees**

| Violation | Fine |
|-----------|------|
| Text enablement without authorization | $10,000 |
| Content violation (SHAFT-C, spam, phishing) | $10,000 |
| Program evasion (snowshoeing, dynamic routing) | $1,000 |
| Fraud (phishing, smishing) | $2,000 |
| Illegal content (cannabis, etc.) | $1,000 |
| Other SHAFT violations | $500 |

### SHAFT-C Content (Prohibited)
- **S**ex
- **H**ate
- **A**lcohol
- **F**irearms
- **T**obacco
- **C**annabis

---

## Fees Summary

### Registration Fees
| Item | Cost |
|------|------|
| Brand registration | $4.50 one-time |
| Campaign vetting | $15 per submission |
| Campaign maintenance | $1.50-$10/month |

### Per-Message Carrier Fees (Registered)

| Carrier | SMS | MMS |
|---------|-----|-----|
| T-Mobile | $0.003 | $0.010 |
| AT&T | $0.003 | $0.0075 |
| Verizon | $0.0031 | $0.0052 |
| US Cellular | $0.005 | $0.010 |

### Per-Message Carrier Fees (Unregistered) ⚠️

| Carrier | SMS | MMS |
|---------|-----|-----|
| T-Mobile | $0.012 | $0.021 |
| AT&T | $0.010 | $0.015 |

---

## Sole Proprietor Registration

For individuals without an EIN:

### Constraints
- 1 campaign per brand
- 1 phone number per campaign
- Low throughput
- Requires SMS OTP verification

### Process
1. Create brand with `entityType: "SOLE_PROPRIETOR"`
2. Trigger OTP verification to mobile phone
3. Verify PIN
4. Create campaign with `usecase: "SOLE_PROPRIETOR"`
5. Assign single phone number

---

## Quick Links

- [Telnyx 10DLC Portal](https://portal.telnyx.com/#/messaging-10dlc)
- [10DLC API Reference](https://developers.telnyx.com/api/messaging/10dlc)
- [TCR Campaign Registry](https://www.campaignregistry.com/)
- [Telnyx 10DLC FAQ](https://support.telnyx.com/en/articles/3679260)
- [10DLC Fees](https://support.telnyx.com/en/articles/5634625)
