# Re-Connect

## Database migration

Run `database/migrations/2026_11_26_add_registrar_ids_and_appointments.sql`
once against the `reconnect_db` database before using the updated login or
appointment pages. The migration adds numeric registrar IDs, creates the
appointment table, and seeds a demo student account.

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

## Appointments

Students can request appointments from the Appointments page. The date picker
blocks past dates, and the server also rejects past dates. Registrars can review
requests and update their status from their dashboard's Appointments page.
Appointment dates use UTC+08 as the school calendar timezone.