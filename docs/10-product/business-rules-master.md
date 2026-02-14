# Business Rules Master

## Ticket Numbering
- Format: `RR-YYYYMMDD-NNNN`
- Auto-increments per day
- Generated on service request creation

## Document Chain
- Service Request → Estimate → Work Order → Invoice → Receipt
- Each downstream document inherits customer, vehicle, and location data
- Change Orders can modify Work Orders mid-execution

## Status Transitions
### Service Requests
`pending` → `dispatched` → `in_progress` → `completed` → `closed`

### Estimates
`draft` → `sent` → `approved` → `rejected`

### Work Orders
`pending` → `in_progress` → `completed`

### Invoices
`draft` → `sent` → `paid` → `overdue` → `void`

## Pricing
- Line items: description, quantity, unit price, total
- Tax calculation: configurable rate
- Discount support on estimates and invoices

## Last Updated
2026-02-12