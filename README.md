# CU Account Officer — Refund Crediting

The Account Officer portal completes the final operational refund stage.

## Page

- `accountofficer/dashboard.php`

The default queue shows only `approved` refunds. Search is server-side by reference ID, matric number, or student name. The optional filter can show credited or all operational refunds.

## Workflow

The Account Officer can inspect safe student, application, payment, stored refund amount, approval, and actual lifecycle-event details. **Mark as Credited** is an operational confirmation; it does not send money through a banking or payment-provider API.

The shared lifecycle service enforces `approved → credited`, records `creditedat`, `creditedby`, and one `refund_credited` event. The refund amount comes from `idcardrefunds` and cannot be changed by the browser. Duplicate credit is rejected through transactional locking.

## Authentication and configuration

Production requires `CU_ACCOUNTOFFICER_IDENTITY_RESOLVER`, resolving a portal session login to `account_officer`.

For local testing:

```env
CU_AUTH_BYPASS=1
CU_AUTH_BYPASS_ACCOUNT_ACTOR=DEV_ACCOUNT_OFFICER
```

CSRF remains required. 

## API

- `GET index.php?action=session`
- `GET index.php?action=refunds&search=&filter=approved|credited|all`
- `GET index.php?action=details&ref=`
- `POST index.php?action=creditrefund` with `X-CSRF-Token`
