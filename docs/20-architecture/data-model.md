# Data Model + ERD

## Core Tables
| Table | Purpose |
|-------|---------|
| users | Admin/technician login accounts |
| customers | Customer contact and address info |
| service_requests | Incoming service tickets with vehicle/location |
| technicians | Tech profiles, skills, certifications |
| estimates | Cost projections linked to service requests |
| estimate_items | Line items for estimates |
| work_orders | Authorized work from approved estimates |
| work_order_items | Line items for work orders |
| change_orders | Modifications to active work orders |
| invoices | Bills generated from completed work |
| invoice_items | Line items for invoices |
| receipts | Payment records for invoices |
| expenses | Operating cost tracking |
| compliance_documents | Licenses, insurance, certifications |
| services | Service catalog entries |
| service_categories | Category groupings for services |

## Director Tables (Meta)
| Table | Purpose |
|-------|---------|
| director_artifacts | Project documentation registry |
| director_dependencies | Artifact dependency graph |
| director_tasks | Build queue / kanban items |
| director_quality_gates | Task completion criteria |
| director_decisions | Architecture Decision Records |
| director_releases | Version/release tracking |
| director_audit_log | All Director action history |

## Key Relationships
- service_requests → customers (customer_id)
- estimates → service_requests (service_request_id)
- work_orders → estimates (estimate_id)
- invoices → work_orders (work_order_id)
- receipts → invoices (invoice_id)

## Last Updated
2026-02-12