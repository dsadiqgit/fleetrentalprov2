# Project Development Rules - Fleet Rental Pro

## UI & UX Conventions
- **No Native Dialogs**: Never use standard browser `alert()` or `confirm()` functions.
- **Custom Modals**: Always use the custom messaging system:
    - `showNotification(message, type)`: For success/error toasts (located in `includes/confirmation-modal.php`).
    - `showConfirmation(title, message, callback, ...)`: For action confirmations (located in `includes/confirmation-modal.php`).
- **Soft Deletion**: For critical data like bookings, always implement a soft deletion pattern using an `is_deleted` flag instead of removing records from the database.
- **Deletion Restrictions**: Bookings can ONLY be deleted (moved to soft-delete) if their status is `cancelled`.

## Naming Conventions
- **PHP Scripts**: Use kebab-case for API and dashboard scripts (e.g., `delete-booking.php`, `restore-booking.php`).
- **Database**: Use snake_case for table and column names.
