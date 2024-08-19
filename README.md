# Eveltic Cookie Bundle
The Eveltic Cookie Consent Bundle is a Symfony package designed to help developers implement and manage user cookie consent on their websites, ensuring full compliance with the latest 2024 AEPD (Spanish Data Protection Agency) regulations. This bundle provides an easy-to-use interface for configuring, collecting, and storing user consent preferences, with customizable options for different types of cookies (technical, analytics, preferences, and advertisement). The package includes ready-to-use templates, supports multiple languages, and seamlessly integrates with Symfony applications, making it easier to align your website with legal requirements.

## Images
### Banner
![image](https://github.com/user-attachments/assets/a85bdea7-112d-45f6-84ac-796f0f726508)
## Configure cookies
![image](https://github.com/user-attachments/assets/7f6d3992-321a-4cc5-aada-c0c3b7a149d1)
## Retract from consent
### Floating button
![image](https://github.com/user-attachments/assets/f05f91a3-5beb-42e1-8059-c51ad444ffa6)
### Retract button and consent details
![image](https://github.com/user-attachments/assets/aa399f8a-2929-4e93-967f-99776be9e3d0)
![image](https://github.com/user-attachments/assets/3c858473-498c-4304-ac83-14d4a6a69af9)


## Features
- Manage cookie consent categories: technical, preferences, analytics, and ads (you can configure all categories adding or removing according to your needs).
- Configure expiration period for cookies.
- Support for light, dark, and auto theme modes.
- Command line tools for archiving cookie consent data.
- Integration with Doctrine ORM.
- Fully featured twig display.
- Twig and backend functions to check if consent is accepted.

## Installation
1. Require the Bundle via Composer:
This command will download and install this bundle inside your composer folder.
```bash
composer require eveltic/cookie-bundle
```
2. Run install command
This command will create the necesary files in order to be able to adequate to your configuration needs.
```bash
php bin/console ev:cookies:install
```
3. Update the database
If you force update the database:
```bash
php bin/console doctrine:schema:update --force
```
or if you are using migrations:
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```
## Configuration
If you ran the installation command all configuration files were in their corresponding folder.
If you dont ran the command and you want to configure these are the files you need:
> config/packages/eveltic_cookie.yaml
```yaml
eveltic_cookie: 
    categories:
      - name: "technical"
        required: true
      - name: "preferences"
        required: false
      - name: "analytics"
        required: false
      - name: "ads"
        required: false
    domain: null
    expiration: "+2 years"
    version: 1
    theme_mode: auto # 'light', 'dark', 'auto'
```
### Available Configuration Options
- `categories`: Define the categories of cookies and whether they are required.
- `domain`: Set the domain for the cookies.
- `expiration`: Set the cookie expiration period.
- `version`: Specify the version of your cookie consent policy.
- `theme_mode`: Choose the theme mode for the consent dialog ('light', 'dark', 'auto').

> config/routes/eveltic_cookie.yaml
```yaml
eveltic_cookie:
    resource:
        path: '@EvelticCookieBundle/src/Controller/'
        namespace: Eveltic\CookieBundle\Controller
    type: attribute
    prefix: /
```

## Usage
### Functions
#### Twig Functions

> {{ ev_cookie_render() }}

Renders the cookie consent banner. This function generates the HTML content for displaying the cookie consent dialog based on the user's current preferences.
This function should be included in a layout or template where you want the cookie consent banner to appear.
> {{ ev_cookie_is_category_accepted('analytics') }}

Checks if a specific cookie category (e.g., "technical", "analytics") is accepted by the user.
This function is useful for conditionally loading scripts or other assets based on the user's consent for specific categories.
> {{ ev_cookie_show_cookie_banner() }}

Determines if the cookie consent banner should be shown to the user. This typically checks if the user has already provided consent.
This function allows you to control whether the consent banner should be displayed, avoiding unnecessary re-rendering.
> {{ ev_cookie_include_js() }}

Includes the JavaScript necessary for handling cookie consent interactions on the frontend.
Place this in your template to ensure the necessary JavaScript is included for managing cookie consent.
> {{ ev_cookie_include_css() }}

Includes the CSS styles for the cookie consent banner.
This function is used to add the styling needed for the cookie consent banner.
> {{ ev_cookie_include_assets() }}

Includes both the JavaScript and CSS assets required for the cookie consent banner in one go.
This function is a convenience method to include both the JavaScript and CSS with a single function call, making it easier to integrate into your templates.

Usage Example
 ```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Website</title>
    {{ ev_cookie_include_assets() }}
</head>
<body>
    {{ ev_cookie_render() }}

    {% if ev_cookie_is_category_accepted('analytics') %}
        <!-- Create analytics cookies -->
    {% endif %}

    <!-- Your content here -->

</body>
</html>
```

#### Backend Functions Provided by CookieConsentService
> $cookieConsentService->getConsent()

Retrieves the current cookie consent data from the user's session or cookies.
```php
$consent = $cookieConsentService->getConsent();

if ($consent) {
    // Process the consent data
} else {
    // Handle the case where no consent has been given
}
```
> $cookieConsentService->hasConsented()

Checks whether the user has already provided consent for cookies. This function can be used to determine whether to display the cookie consent banner.
```php
if ($cookieConsentService->hasConsented()) {
    // User has already consented
} else {
    // Show consent banner or take appropriate action
}
```
> $cookieConsentService->isCategoryAccepted(string $category)

Determines if a specific cookie category has been accepted by the user.
  - Parameters:
    - $category: The name of the cookie category you want to check (e.g., "analytics", "preferences").
```php
if ($cookieConsentService->isCategoryAccepted('analytics')) {
    // Load analytics scripts or perform actions that require analytics cookies
}
```

### Commands
```command
php bin/console ev:cookie:install
```
Installs the necessary assets and settings for the cookie consent management.
```command
php bin/console ev:cookie:archive
php bin/console ev:cookie:archive --date=2023-01-01 --output-format=csv
php bin/console ev:cookie:archive --date=2023-01-01 --dry-run
```
Archives old cookie consent data to keep your database clean.
#### Parameters
- `--date`: (optional) Allows you to specify a date in the Y-m-d format (e.g., 2023-01-01). This parameter indicates that all records before this date will be archived.
- `--output-format`: (optional) Specifies the output format for the archived records. Available options are:
  - `log`: Saves the archived records in a LOG file.
  - `csv`: Saves the archived records in a CSV file.
  - `html`: Saves the archived records in an HTML file.
- `--dry-run`: (optional) Performs a simulation of the archiving process without actually modifying any data in the database. This is useful for previewing which records would be archived without making permanent changes.

## Translations
This bundle supports multiple languages for the consent banner. Available translations include:
- German (de)
- Spanish (es)
- French (fr)
- Italian (it)
- Dutch (nl)
- Polish (pl)
- Portuguese (pt)
- Russian (ru)

## Contributing
Contributions are welcome! Please fork the repository and submit a pull request for any features, bug fixes, or improvements.

## License
This bundle is released under the MIT License. See the LICENSE file for more details. Feel free to use in any condition and modify all as you want.

