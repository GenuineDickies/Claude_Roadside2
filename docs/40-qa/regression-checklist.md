# Regression Checklist

Run after any significant change:

## Authentication
- [ ] Login with valid creds → dashboard
- [ ] Login with bad creds → error message
- [ ] Logout → redirected to login
- [ ] Direct page access without session → login redirect

## Core Workflow
- [ ] Create customer → appears in list
- [ ] Create service request → ticket number generated
- [ ] Assign technician → status updates to dispatched
- [ ] Create estimate from SR → line items work
- [ ] Approve estimate → work order created
- [ ] Complete work order → ready for invoice
- [ ] Generate invoice → line items carried over
- [ ] Record payment → receipt created

## Pages Load Without Error
- [ ] Dashboard
- [ ] Service Requests
- [ ] New Intake
- [ ] Customers
- [ ] Technicians
- [ ] Estimates
- [ ] Work Orders
- [ ] Change Orders
- [ ] Invoices
- [ ] Receipts
- [ ] Expenses
- [ ] Compliance
- [ ] Services (React catalog)
- [ ] Director

## Last Updated
2026-02-12