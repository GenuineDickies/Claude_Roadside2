# API Contracts & Integrations

## Internal API Endpoints

### Service Tickets
- `GET api/service-tickets.php?action=list` → List all requests
- `POST api/service-tickets.php` (action=create) → Create request
- `POST api/service-tickets.php` (action=update_status) → Update status

### Workflow
- `GET api/workflow.php?action=get_estimates` → List estimates
- `POST api/workflow.php` (action=create_estimate) → Create estimate

### Technicians
- `POST api/assign_technician.php` → Assign tech to SR
- `POST api/update_status.php` → Update SR status

### Director
- `GET api/director.php?action=get_stats` → Dashboard stats
- `GET api/director.php?action=get_artifacts` → Artifact list
- `POST api/director.php` (action=update_artifact_status) → Change status
- `GET api/director.php?action=get_tasks` → Build queue
- `POST api/director.php` (action=create_task) → New task

### Expenses
- `GET api/expenses.php?action=list` → List expenses
- `POST api/expenses.php` (action=create) → Log expense

### Compliance
- `GET api/compliance.php?action=list` → List documents
- `POST api/compliance.php` (action=upload) → Upload document

## Response Format
All APIs return: `{ "success": true|false, "data": [...], "error": "message" }`

## External Integrations
None currently. Future: SMS gateway, payment processor.

## Last Updated
2026-02-12