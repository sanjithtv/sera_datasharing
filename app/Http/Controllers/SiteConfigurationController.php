<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Models\SiteConfiguration;

class SiteConfigurationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:manage-settings']);
    }

    /** Show configuration page */
    public function index()
    {
        $settings = [
            // Main
            'app_title'       => SiteConfiguration::getValue('app_title', 'My Application'),
            'app_description' => SiteConfiguration::getValue('app_description', ''),
            'app_logo'        => SiteConfiguration::getValue('app_logo', null),

            // Email
            'mail_from_name'  => SiteConfiguration::getValue('mail_from_name', ''),
            'mail_from_email' => SiteConfiguration::getValue('mail_from_email', ''),
            'smtp_host'       => SiteConfiguration::getValue('smtp_host', ''),
            'smtp_port'       => SiteConfiguration::getValue('smtp_port', ''),
            'smtp_username'   => SiteConfiguration::getValue('smtp_username', ''),
            'smtp_password'   => SiteConfiguration::getValue('smtp_password', ''),

            // Security
            'password_policy'      => SiteConfiguration::getValue('password_policy', 'medium'),
            'session_timeout'      => SiteConfiguration::getValue('session_timeout', 30),
            'max_login_attempts'   => SiteConfiguration::getValue('max_login_attempts', 5),
            'lockout_minutes'      => SiteConfiguration::getValue('lockout_minutes', 15),
            'two_factor_auth'      => SiteConfiguration::getValue('two_factor_auth', 'disabled'),
            'password_expiry_days' => SiteConfiguration::getValue('password_expiry_days', 90),
        ];

        return view('modules.site_configuration.index', compact('settings'));
    }

    /** Save configuration */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // main
            'app_title'       => 'nullable|string|max:255',
            'app_description' => 'nullable|string|max:500',
            'app_logo'        => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',

            // email
            'mail_from_name'  => 'nullable|string|max:255',
            'mail_from_email' => 'nullable|email|max:255',
            'smtp_host'       => 'nullable|string|max:255',
            'smtp_port'       => 'nullable|string|max:10',
            'smtp_username'   => 'nullable|string|max:255',
            'smtp_password'   => 'nullable|string|max:255',

            // security
            'password_policy'      => 'nullable|string|max:50',
            'session_timeout'      => 'nullable|numeric|min:1|max:480',
            'max_login_attempts'   => 'nullable|integer|min:1|max:20',
            'lockout_minutes'      => 'nullable|integer|min:1|max:120',
            'two_factor_auth'      => 'nullable|in:disabled,email,app',
            'password_expiry_days' => 'nullable|integer|min:0|max:365',
        ]);

        // Handle logo
        if ($request->hasFile('app_logo')) {
            $path = $request->file('app_logo')->store('logos', 'public');
            SiteConfiguration::setValue('app_logo', $path);
        }

        // Save all values
        foreach ($validated as $key => $value) {
            if ($key !== 'app_logo' || !$request->hasFile('app_logo')) {
                SiteConfiguration::setValue($key, $value);
            }
        }

        // Sync .env for mail settings
        $this->updateEnv([
            'MAIL_MAILER'     => 'smtp',
            'MAIL_HOST'       => $validated['smtp_host'] ?? '',
            'MAIL_PORT'       => $validated['smtp_port'] ?? '',
            'MAIL_USERNAME'   => $validated['smtp_username'] ?? '',
            'MAIL_PASSWORD'   => $validated['smtp_password'] ?? '',
            'MAIL_FROM_NAME'  => $validated['mail_from_name'] ?? '',
            'MAIL_FROM_ADDRESS'=> $validated['mail_from_email'] ?? '',
            'APP_NAME'         => $validated['app_title'],
        ]);

        return back()->with('success', 'Settings updated successfully.');
    }

    /** Helper to safely update .env values */
    private function updateEnv(array $data): void
    {
        $envPath = base_path('.env');
        if (!File::exists($envPath)) return;

        $content = File::get($envPath);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $line = $key . '=' . '"' . addslashes($value) . '"';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $line, $content);
            } else {
                $content .= "\n{$line}";
            }
        }

        File::put($envPath, $content);
    }
}

