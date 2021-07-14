# Récolte-Links

## About Récolte-Links

Récolte-Links is a custom web app built using the Laravel framework. It was created by <a href="https://cliambrown.com/" target="_blank">C. Liam Brown</a> for internal use by <a href="https://recolte.ca" target="_blank">Collectif Récolte</a> as a way to share and manage external links in conjuction with Slack.

If you would like to install this app for your organization, please contact Liam: <a href="mailto:liam@recolte.ca">liam@recolte.ca</a>

## Requirements

- see [Laravel requirements](https://laravel.com/docs/8.x/installation)
- Composer
- npm (dev environment only)
- MySQL
- Slack pro (or nonprofit) account

## Installation

1. SSH into webhost and navigate to one level above `public_html`
1. `git clone https://github.com/cliambrown/recolte-links.git`
1. `cd recolte-links`
1. `composer install` (may need to install composer first)
1. `npm install` (optional — dev environment installs only)
1. `cp .env.example .env`
1. `php artisan key:generate`
1. Create a MySQL database + privileged user
1. Add db info from previous step to `.env` file
1. Update `.env` with required info
   * <a href="https://api.slack.com/apps?new_app=1" target="_blank">Create a Slack App</a>
   * Add the App to the desired link-sharing channel
   * Add all required Slack info to `.env`
1. `php artisan migrate` (optional: ` -seed`)
1. On cPanel or equivalent, add a new subdomain pointing directly to `/recolte-links/public`
1. Make sure the domain redirects all requests to https (e.g. with `.htaccess`)

## License

Récolte-Links is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
