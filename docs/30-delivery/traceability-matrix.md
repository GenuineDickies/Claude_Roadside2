# Traceability Matrix

| Requirement | Page | API | DB Table | Status |
|------------|------|-----|----------|--------|
| Customer management | customers.php | — (inline) | customers | Done |
| Service request intake | service-intake.php | service-tickets.php | service_requests | Done |
| Service request listing | service-requests.php | service-tickets.php | service_requests | Done |
| Technician management | technicians.php | — (inline) | technicians | Done |
| Technician dispatch | service-requests.php | assign_technician.php | service_requests | Done |
| Estimates | estimates.php | workflow.php | estimates, estimate_items | Done |
| Work orders | work-orders.php | workflow.php | work_orders, work_order_items | Done |
| Change orders | change-orders.php | — (inline) | change_orders | Done |
| Invoices | invoices-v2.php | — (inline) | invoices, invoice_items | Done |
| Receipts | receipts.php | — (inline) | receipts | Done |
| Expenses | expenses.php | expenses.php | expenses | Done |
| Compliance | compliance.php | compliance.php | compliance_documents | Done |
| Service catalog | services.php (React) | — | services, service_categories | Done |
| Dashboard | dashboard.php | — (inline) | multiple | Done |
| Director | director.php | director.php | director_* | Done |

## Last Updated
2026-02-12