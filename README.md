# Fleet Rental Pro - PHP + MySQL SaaS

**Multi-tenant Car Rental Management System**  
Built for shared hosting (Namecheap, HostGator, etc.) - No Node.js required!

## 🚀 Features

- **Multi-Tenant Architecture** - Each company gets isolated data
- **Subdomain Support** - `company.fleetrentalpro.com`
- **Vehicle Management** - Fleet tracking, pricing, availability
- **Booking System** - Calendar, reservations, customer management
- **Digital Contracts** - Templates and signatures
- **Custom Websites** - Each tenant gets a public booking site
- **Role-Based Access** - Super Admin, Admin, Customer roles

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite (or Nginx)
- Shared hosting compatible

## 🛠 Installation

### 1. Upload Files

Upload all files to your `public_html` folder via FTP or cPanel File Manager.

### 2. Create MySQL Database

1. Go to cPanel → MySQL Databases
2. Create a new database (e.g., `youruser_fleetrental`)
3. Create a MySQL user with a strong password
4. Add the user to the database with ALL PRIVILEGES

### 3. Import Database Schema

1. Go to phpMyAdmin
2. Select your database
3. Click "Import"
4. Upload `database/schema.sql`
5. Click "Go"

### 4. Configure Database Connection

Edit `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'youruser_fleetrental');
define('DB_USER', 'youruser_dbuser');
define('DB_PASS', 'your_password');
```

### 5. Update Site Settings

Edit `config/config.php`:

```php
define('SITE_URL', 'https://fleetrentalpro.com');
define('ROOT_DOMAIN', 'fleetrentalpro.com');
```

### 6. Set Up Wildcard Subdomain

**In cPanel:**

1. Go to "Subdomains"
2. Create subdomain: `*` (asterisk)
3. Document Root: `/public_html/tenants`
4. Save

**DNS Settings:**

Add a wildcard A record:
- Type: A
- Name: `*`
- Points to: Your server IP

### 7. Create Uploads Folder

```bash
mkdir uploads
chmod 755 uploads
```

## 🔐 Default Super Admin Login

- Email: `admin@fleetrentalpro.com`
- Password: `admin123`

**⚠️ Change this immediately after first login!**

## 📁 Project Structure

```
public_html/
├── auth/
│   ├── login.php
│   ├── signup.php
│   └── logout.php
├── config/
│   ├── config.php
│   └── database.php
├── includes/
│   ├── tenant_init.php
│   └── functions.php
├── database/
│   └── schema.sql
├── tenants/
│   └── [auto-created per tenant]
├── uploads/
│   └── [user uploads]
├── index.php
└── README.md
```

## 🌐 How Multi-Tenancy Works

1. User signs up → Creates tenant in database
2. Tenant gets subdomain (e.g., `hotcar.fleetrentalpro.com`)
3. All requests to `*.fleetrentalpro.com` route to `/tenants/`
4. `tenant_init.php` extracts subdomain and loads tenant data
5. All database queries filter by `tenant_id`

## 🚗 Creating a New Tenant

**Via Signup Page:**
1. Go to `/auth/signup.php`
2. Enter company name, email, password
3. Subdomain auto-generates from company name
4. Tenant folder created automatically

**Manual Setup:**
1. Insert into `tenants` table
2. Create user with `role = 'admin'`
3. Access at `subdomain.fleetrentalpro.com`

## 📊 Database Tables

- `tenants` - Company information
- `users` - All users (super admin, admins, customers)
- `vehicles` - Fleet vehicles per tenant
- `bookings` - Rental reservations
- `contracts` - Digital contracts
- `contract_templates` - Reusable templates
- `tenant_settings` - Company settings

## 🔧 Customization

**Branding:**
- Logo upload
- Primary/secondary colors
- Custom domain (Growth+ plans)

**Settings:**
- Minimum/maximum rental days
- Pricing rules
- Terms & conditions
- Cancellation policy

## 🐛 Troubleshooting

**Issue: Database connection fails**
- Check credentials in `config/database.php`
- Verify database exists in cPanel
- Confirm user has privileges

**Issue: Subdomains not working**
- Verify wildcard subdomain is set up
- Check DNS propagation (can take 24-48 hours)
- Ensure `.htaccess` exists

**Issue: Upload errors**
- Check folder permissions: `chmod 755 uploads`
- Verify `upload_max_filesize` in php.ini

**Issue: Session errors**
- Check `session.save_path` permissions
- Verify PHP sessions are enabled

## 📦 Deployment to Shared Hosting

1. **Zip the project:**
   ```bash
   zip -r fleetrental.zip *
   ```

2. **Upload via cPanel:**
   - Go to File Manager
   - Navigate to `public_html`
   - Upload `fleetrental.zip`
   - Extract

3. **Set permissions:**
   ```bash
   chmod 755 uploads
   chmod 644 config/*.php
   ```

4. **Configure database** (steps above)

5. **Test:**
   - Visit your domain
   - Sign up a test tenant
   - Access tenant subdomain

## 🎯 Next Steps

After installation:

1. Change super admin password
2. Configure email settings (for notifications)
3. Add payment gateway (Stripe/PayPal)
4. Customise landing page
5. Add your branding

## 📝 License

MIT License - Use freely for your projects

## 🆘 Support

For issues or questions:
- Check troubleshooting section
- Review PHP/MySQL error logs in cPanel
- Verify all file permissions

---

**Built for shared hosting - Deploy anywhere!**
