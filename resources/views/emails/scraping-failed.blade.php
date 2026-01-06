<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
  <meta charset="utf-8">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="format-detection" content="telephone=no, date=no, address=no, email=no, url=no">
  <!--[if mso]>
  <noscript>
    <xml>
      <o:OfficeDocumentSettings xmlns:o="urn:schemas-microsoft-com:office:office">
        <o:PixelsPerInch>96</o:PixelsPerInch>
      </o:OfficeDocumentSettings>
    </xml>
  </noscript>
  <style>
    td,th,div,p,a,h1,h2,h3,h4,h5,h6 {font-family: "Segoe UI", sans-serif; mso-line-height-rule: exactly;}
  </style>
  <![endif]-->
  <title>Scraping Failed</title>
  <style>
    body { margin: 0; padding: 0; width: 100%; word-break: break-word; -webkit-font-smoothing: antialiased; }
    .btn-primary:hover { background-color: #334155 !important; }
  </style>
</head>
<body style="margin: 0; padding: 0; width: 100%; word-break: break-word; -webkit-font-smoothing: antialiased; background-color: #f1f5f9; font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; color: #334155;">
  <div role="article" aria-roledescription="email" lang="en" style="margin: 40px auto; width: 100%; max-width: 600px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); overflow: hidden;">

    <!-- Header -->
    <div style="background-color: #dc2626; padding: 32px; text-align: center; color: #ffffff;">
      <h1 style="margin: 0; font-size: 30px; font-weight: 700;">⚠️ Scraping Alert</h1>
      <p style="margin-top: 8px; margin-bottom: 0; color: #fee2e2; opacity: 0.9;">Currency Hub System Notification</p>
    </div>

    <!-- Body -->
    <div style="padding: 32px;">
      <h2 style="font-size: 20px; font-weight: 600; color: #1e293b; margin-bottom: 16px;">Critical Error Detected</h2>
      <p style="margin-bottom: 24px; font-size: 16px; line-height: 1.5; color: #475569;">
        The scheduled currency scraping job has failed after <strong>3 consecutive attempts</strong>. This requires immediate attention to ensure data continuity.
      </p>

      <!-- Error Details Box -->
      <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 16px; margin-bottom: 24px;">
        <h3 style="margin: 0 0 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #ef4444; font-weight: 700;">Error Details:</h3>
        <code style="display: block; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 14px; color: #b91c1c; word-break: break-all;">
            {{ $errorMessage }}
        </code>
      </div>

      <!-- Action Button -->
      <div style="text-align: center; margin: 32px 0;">
        <a href="{{ url('/') }}" class="btn-primary" style="display: inline-block; padding: 12px 24px; background-color: #1e293b; color: #ffffff; font-weight: 600; text-decoration: none; border-radius: 6px; transition: background-color 0.2s;">
          Open Dashboard & Check Logs
        </a>
      </div>

      <p style="margin: 0; font-size: 14px; color: #64748b;">
        If this persists, please verify the spider selectors and the target website availability (cuantoestaeldolar.pe).
      </p>
    </div>

    <!-- Footer -->
    <div style="background-color: #f8fafc; padding: 24px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9;">
      <p style="margin: 0;">&copy; {{ date('Y') }} Currency Hub API. Automated System Message.</p>
    </div>

  </div>
</body>
</html>
