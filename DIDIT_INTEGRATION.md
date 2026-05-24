# Didit Identity Verification Integration

## Overview
This document explains how Didit identity verification has been integrated into the vehicle booking checkout flow.

## Integration Flow

The booking process now follows a **4-step checkout flow**:

### Step 1: Customer Details
- Customer enters personal information (name, email, phone, license number)
- Selects rental dates and times
- No payment required at this stage
- Data is saved to PHP session

### Step 2: Identity Verification (Didit)
- Didit verification iframe is loaded
- Customer completes identity verification through Didit
- Verification includes:
  - ID document scanning
  - Liveness detection
  - Face matching
- Once verified, customer can proceed to payment

### Step 3: Payment
- Customer selects payment method:
  - Credit/Debit Card
  - Pay at Pickup
- Card details collected (if card payment selected)
- Booking is created in database

### Step 4: Confirmation
- Booking confirmation displayed
- Booking ID shown to customer
- Session data cleared

## Files Created/Modified

### New Files
1. **`/templates/checkout.php`** - Main 4-step checkout page
2. **`/templates/save-booking-data.php`** - Saves customer data to session
3. **`/templates/update-checkout-step.php`** - Updates current checkout step
4. **`/templates/didit-webhook.php`** - Handles Didit verification webhooks

### Modified Files
1. **`/templates/process-booking.php`** - Updated to use session data
2. **`/templates/vehicle-details.php`** - Links to new checkout
3. **`/templates/fleet.php`** - Links to new checkout

## Didit Configuration

### Application ID
The Didit application ID is configured in the checkout page:
```javascript
const diditAppId = '3b64939d-1ba7-42df-b602-344cbc78e387';
```

### Verification URL
The iframe loads the Didit verification interface:
```
https://business.didit.me/verify/{APP_ID}?session={SESSION_ID}
```

### Webhook Endpoint
Configure this webhook URL in your Didit dashboard:
```
https://yourdomain.com/templates/didit-webhook.php
```

## Session Management

The checkout flow uses PHP sessions to maintain state:

- **`$_SESSION['checkout_step']`** - Current step (1-4)
- **`$_SESSION['booking_data']`** - Customer and rental information
- **`$_SESSION['didit_session_id']`** - Unique session ID for Didit
- **`$_SESSION['verification_status']`** - Verification result (approved/declined/in_review)
- **`$_SESSION['verification_data']`** - Full verification response from Didit

## Security Considerations

1. **Webhook Signature Verification** - Currently commented out in `didit-webhook.php`. Implement signature verification based on Didit documentation.

2. **Session Security** - Ensure PHP sessions are configured securely:
   - Use HTTPS in production
   - Set secure session cookies
   - Implement session timeout

3. **Data Validation** - All customer data is validated before processing

## Testing the Integration

1. Navigate to a vehicle on the fleet page
2. Click "Book Now"
3. Fill in customer details on Step 1
4. Complete Didit verification on Step 2
5. Select payment method on Step 3
6. View confirmation on Step 4

## Customization

### Changing Didit App ID
Update the `diditAppId` constant in `/templates/checkout.php`:
```javascript
const diditAppId = 'YOUR_APP_ID_HERE';
```

### Styling
The checkout page uses Tailwind CSS. Modify classes in `checkout.php` to match your brand.

### Adding Verification Requirements
Configure verification requirements in your Didit Business Console:
- ID verification
- Liveness detection
- Face matching
- AML screening
- Age verification

## Troubleshooting

### Verification iframe not loading
- Check that the Didit app ID is correct
- Verify the session ID is being generated properly
- Check browser console for errors

### Webhook not receiving events
- Verify webhook URL is configured in Didit dashboard
- Check server logs for incoming requests
- Ensure webhook endpoint is publicly accessible

### Session data lost
- Check PHP session configuration
- Verify session cookies are being set
- Check for session timeout issues

## Support

For Didit-specific issues:
- Didit Documentation: https://docs.didit.me
- Didit Support: support@didit.me

For integration issues:
- Check server error logs
- Review browser console for JavaScript errors
- Verify database connections
