# Workflow: Service Lifecycle

```
Customer Call/Request
       │
       ▼
┌─────────────┐
│  New Intake  │  Capture: customer, vehicle, location, service needed
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Dispatch    │  Assign technician based on skills, proximity, availability
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Diagnosis   │  Tech assesses issue, creates estimate with line items
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Estimate    │  Customer approves/rejects; if approved → Work Order
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Work Order  │  Authorized work; change orders if scope changes
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Invoice     │  Bill customer; line items from work order
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Receipt     │  Payment recorded; service request closed
└─────────────┘
```

## Last Updated
2026-02-12