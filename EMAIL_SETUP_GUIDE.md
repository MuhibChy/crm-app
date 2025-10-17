# 📧 Complete Email Setup Guide for CRM

This guide will walk you through setting up email accounts in your CRM system to send and receive emails from multiple domains.

## 🚀 Quick Start

### Step 1: Access Email Account Management
1. Open your CRM application
2. Click on **"⚙️ Accounts"** in the navigation menu
3. You'll see the Email Account Management page

### Step 2: Add Your First Email Account
1. Fill out the form on the left side of the page
2. Follow the provider-specific instructions below
3. Click **"➕ Add Account"** to save

## 📋 Provider-Specific Setup Instructions

### 🔴 Gmail Setup

#### Prerequisites:
1. **Enable 2-Step Verification** in your Google Account
2. **Generate an App Password** (not your regular Gmail password)

#### Steps:
1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Enable 2-Step Verification if not already enabled
3. Go to "App passwords" and generate a new password for "Mail"
4. Use this App Password in the CRM, NOT your regular password

#### Settings:
```
Account Label: Gmail Main Account
IMAP Host: imap.gmail.com
IMAP Port: 993
SMTP Host: smtp.gmail.com  
SMTP Port: 587
Email Address: your-email@gmail.com
Password: [Your App Password - 16 characters]
IMAP SSL: ✅ Enabled
SMTP TLS: ✅ Enabled
```

### 🔵 Outlook/Hotmail Setup

#### Settings:
```
Account Label: Outlook Business
IMAP Host: outlook.office365.com
IMAP Port: 993
SMTP Host: smtp-mail.outlook.com
SMTP Port: 587
Email Address: your-email@outlook.com
Password: [Your Microsoft account password]
IMAP SSL: ✅ Enabled
SMTP TLS: ✅ Enabled
```

#### For Microsoft 365 Business:
```
IMAP Host: outlook.office365.com
SMTP Host: smtp.office365.com
```

### 🟣 Yahoo Mail Setup

#### Prerequisites:
1. Enable "Less secure app access" in Yahoo Mail settings
2. Or generate an App Password (recommended)

#### Settings:
```
Account Label: Yahoo Personal
IMAP Host: imap.mail.yahoo.com
IMAP Port: 993
SMTP Host: smtp.mail.yahoo.com
SMTP Port: 587 or 465
Email Address: your-email@yahoo.com
Password: [Your Yahoo password or App Password]
IMAP SSL: ✅ Enabled
SMTP TLS: ✅ Enabled
```

### 🟠 Custom Domain/Business Email

#### For cPanel/WHM hosting:
```
Account Label: Business Email
IMAP Host: mail.yourdomain.com
IMAP Port: 993
SMTP Host: mail.yourdomain.com
SMTP Port: 587 or 465
Email Address: info@yourdomain.com
Password: [Your email password]
IMAP SSL: ✅ Enabled
SMTP TLS: ✅ Enabled
```

## 🔧 Testing Your Setup

### After Adding an Account:
1. Click the **"🔍 Test"** button next to your account
2. Wait for the connection test results
3. If successful, you'll see "OK" message
4. If failed, check the troubleshooting section below

### Test Sending Email:
1. Go to **"📧 Email Center"** in navigation
2. Select your configured account from dropdown
3. Send a test email to yourself
4. Check if you receive it

### Test Receiving Email:
1. Send an email to your configured account from another email
2. In CRM, click **"📥 Fetch New"** or **"🔄 Sync All"**
3. Check if the email appears in recent emails list

## 🚨 Troubleshooting Common Issues

### ❌ Authentication Failed

**For Gmail:**
- Make sure you're using App Password, not regular password
- Verify 2-Step Verification is enabled
- Check that IMAP is enabled in Gmail settings

**For Outlook:**
- Try using your full email address as username
- For business accounts, check with your IT admin
- Ensure modern authentication is enabled

**For Yahoo:**
- Enable "Less secure app access" or use App Password
- Check Yahoo Mail settings for IMAP access

### ❌ Connection Timeout

**Check Settings:**
- Verify host names are correct (no typos)
- Ensure port numbers match your provider
- Confirm SSL/TLS settings are correct

**Server Issues:**
- Check your server's firewall settings
- Ensure ports 993, 587, 465 are open
- Verify your hosting provider allows IMAP/SMTP

### ❌ SSL Certificate Errors

**Solutions:**
- Try different port numbers (587 vs 465)
- Toggle SSL/TLS settings
- Contact your hosting provider about SSL certificates

## 📊 Managing Multiple Accounts

### Best Practices:
1. **Use descriptive labels** (e.g., "Support Team", "Sales Inquiries")
2. **Test each account** after adding
3. **Keep credentials secure** - never share passwords
4. **Regular monitoring** - check account status periodically

### Account Organization:
- **Main Business**: Primary company email
- **Support**: Customer service emails  
- **Sales**: Sales team communications
- **Personal**: Personal email accounts

## 🔄 Email Synchronization

### Automatic Sync:
- Emails are fetched automatically every 30 seconds
- New emails appear in the Email Center dashboard
- Priority is automatically detected based on subject keywords

### Manual Sync:
- Click **"📥 Fetch New"** for single account sync
- Click **"🔄 Sync All"** to sync all accounts
- Use **"🔄 Refresh"** to reload the page

## 📈 Advanced Features

### AI Email Assistance:
1. Compose an email in Email Center
2. Click **"🤖 AI Assist"** for suggestions
3. AI will analyze content and provide recommendations
4. Choose to accept or modify suggestions

### Email Filtering:
- Emails are automatically categorized by priority
- Status tracking (New, In Progress, Completed)
- Search functionality for finding specific emails

### Bulk Operations:
- Reply to multiple emails
- Mark emails as completed
- Archive old emails
- Export email data

## 🔐 Security Best Practices

### Password Security:
- **Always use App Passwords** when available
- **Never use regular passwords** for email accounts
- **Change passwords regularly**
- **Use strong, unique passwords**

### Account Security:
- **Enable 2FA** on all email accounts
- **Monitor login activity** regularly
- **Review account permissions** periodically
- **Log out from shared computers**

### CRM Security:
- **Keep CRM updated** with latest security patches
- **Use HTTPS** for web access
- **Regular backups** of email data
- **Monitor access logs**

## 📞 Getting Help

### If You're Still Having Issues:

1. **Check the troubleshooting section** above
2. **Verify your email provider's documentation**
3. **Contact your hosting provider** for server-related issues
4. **Check CRM logs** for detailed error messages

### Common Support Resources:
- **Gmail Help**: [support.google.com/mail](https://support.google.com/mail)
- **Outlook Help**: [support.microsoft.com/outlook](https://support.microsoft.com/outlook)
- **Yahoo Help**: [help.yahoo.com/kb/mail](https://help.yahoo.com/kb/mail)

## ✅ Success Checklist

Before considering your setup complete:

- [ ] Email account added successfully
- [ ] Connection test passes
- [ ] Test email sent successfully  
- [ ] Test email received successfully
- [ ] Account appears in Email Center
- [ ] Sync functionality works
- [ ] AI assistance responds (if configured)
- [ ] All team members can access their accounts

## 🎯 Next Steps

After successful setup:

1. **Configure team access** if multiple users
2. **Set up email templates** for common responses
3. **Configure AI settings** for better suggestions
4. **Train team members** on CRM usage
5. **Set up backup procedures** for email data

---

**Need more help?** Check the built-in troubleshooting guide in the CRM or contact your system administrator.

**Last Updated:** October 2024
**Version:** 1.0.0
