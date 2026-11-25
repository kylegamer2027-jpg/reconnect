# Re-Connect

## Database migration

Run `database/migrations/2026_11_26_add_registrar_ids_and_demo.sql` once
against the `reconnect_db` database before using numeric registrar IDs. The
migration adds registrar IDs and seeds a demo student account.

Existing registrar accounts are assigned a registrar ID from their existing
`student_id` value when that value contains digits only. Assign a unique
numeric `registrar_id` to any remaining registrar accounts before they log in.
Accounts without an existing numeric ID receive a generated numeric ID based
on their internal user record ID; replace it with the registrar's official ID
when available.

The demo student account is:

- School ID: `99999999`
- Password: `DemoStudent123!`

This account is student-only and is intended for local testing. Do not retain
the public demo credentials on a production deployment.
