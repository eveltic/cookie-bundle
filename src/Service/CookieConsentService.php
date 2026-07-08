<?php

namespace Eveltic\CookieBundle\Service;

use DateTime;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service for managing cookie consent operations.
 * Handles checking, saving, and retracting cookie consents.
 */
class CookieConsentService
{
    /**
     * The consent cookie name
     */
    const COOKIE_NAME = 'COOKIE_CONSENT';

    /**
     * User consent cookie data. Cached for multiple calls
     */
    private ?array $cachedConsentCookie = null;


    /**
     * Constructor for the CookieConsentService class.
     *
     * @param RequestStack $requestStack 
     *        Provides access to the current request and session data.
     *
     *
     * @param CookieConsentStorageService $storageService 
     *        The service responsible for storing and retrieving cookie consent data from the storage (e.g., database).
     *
     * @param int $currentVersion 
     *        The current version of the cookie consent configuration. Used to track if a new version
     *        of the consent categories is introduced, requiring users to re-consent.
     *
     * @param string $expiration 
     *        The expiration period for the cookie consent, defined in a string format compatible with DateTime (e.g., '+2 years').
     *
     * @param array $categories 
     *        An array of cookie categories defined in the configuration. Each category typically includes a name, description, and whether it is required.
     *
     * @param string $themeMode 
     *        The mode for the cookie consent theme, which can be 'light', 'dark', or 'auto'. Determines the appearance of the cookie consent banner.
     */
    public function __construct(
        private RequestStack $requestStack, 
        private CookieConsentStorageService $storageService, 
        private int $currentVersion, 
        private string $expiration, 
        private array $categories,
        private string $themeMode
        )
    {}

    /**
     * Save user consent.
     *
     * @param array $consentData
     * @return string $cookieValue
     *         Returns the json encoded cookie value to the controller
     */
    public function saveConsent(array $consentData, Response $response, ?string $ip = null, ?string $userAgent = null): string
    {
        $expirationDate = new DateTime();
        $expirationDate->setTimestamp(strtotime($this->expiration));
        $uuid = Uuid::v4();
        $dateTime = new DateTime();

        $cookieValue = json_encode([
            'uuid' => $uuid,
            'datetime' => $dateTime->format('Y/m/d H:i:s'),
            'expiration' => $expirationDate->format('Y/m/d H:i:s'),
            'version' => $this->currentVersion,
            'consentData' => $consentData,
        ]);

        $cookie = Cookie::create(self::COOKIE_NAME, $cookieValue)
                        ->withExpires($expirationDate)
                        ->withPath('/')
                        // ->withSecure(true)
                        ->withHttpOnly(true)
                        ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($cookie);

        $this->storageService->saveConsent($uuid, $consentData, $dateTime, $expirationDate, $ip, $this->currentVersion, $userAgent);
        return $cookieValue;
    }
    
    /**
     * Retract user consent.
     *
     * @return void
     */
    public function retractConsent(Response $response): void
    {
        $response->headers->clearCookie(self::COOKIE_NAME, '/', null, true, true, 'lax');
    }

    /**
     * Check if the consent is given through the user cookie
     *
     * @return string
     */
    public function isConsentGiven(): bool
    {
        $consentData = $this->getConsentCookie();

        if (!isset($consentData['expiration'], $consentData['version'])) {
            return false;
        }

        $currentDate = new DateTime();
        $expirationDate = (new DateTime())->setTimestamp((int) strtotime($consentData['expiration']));

        if ($consentData['version'] === $this->currentVersion &&
            $expirationDate > $currentDate) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve all cookie categories.
     *
     * @return array
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * Retrieve the theme mode
     *
     * @return string
     */
    public function getThemeMode(): string
    {
        return $this->themeMode;
    }

    /**
     * Retrieve the consent cookie name
     *
     * @return string
     */
    public function getCookieName(): string
    {
        return self::COOKIE_NAME;
    }

    /**
     * Retrieve the consent cookie contents.
     *
     * @return array|null
     *         Returns the decoded consent data from the cookie, or null if the cookie does not exist.
     */
    public function getConsentCookie(): ?array
    {
        // If the result is already cached, return it.
        if ($this->cachedConsentCookie !== null) {
            return $this->cachedConsentCookie;
        }

        // Retrieve the current request from the request stack.
        $request = $this->requestStack->getCurrentRequest();

        // Get the value of the consent cookie using the defined cookie name.
        $cookieValue = $request?->cookies->get(self::COOKIE_NAME);

        // No consent cookie present yet — nothing to decode.
        if (!is_string($cookieValue) || '' === $cookieValue) {
            return $this->cachedConsentCookie = null;
        }

        // Decode the JSON-encoded cookie value into an associative array.
        try {
            // Attempt to decode the JSON-encoded cookie value into an associative array.
            $this->cachedConsentCookie = json_decode($cookieValue, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            // If decoding fails, cache the null result and return null.
            $this->cachedConsentCookie = null;
            return null;
        }

        // Return the cached consent data array.
        return $this->cachedConsentCookie;
    }
    
    /**
     * Retrieve the cookie current version
     *
     * @return string
     */
    public function getCurrentVersion(): int
    {
        return $this->currentVersion;
    }

    /**
     * Check if a user has given consent for the specified categories.
     *
     * @param array $categories
     * @return bool
     */
    public function isCategoryAccepted($categories): bool
    {
        if(!$this->isConsentGiven()) {
            return false;
        }

        if (!is_array($categories)) {
            $categories = [$categories];
        }

        $consentCookie = $this->getConsentCookie();
        
        if(isset($consentCookie['consentData'])){
            foreach($categories as $category){
                if(isset($consentCookie['consentData'][$category]) AND $consentCookie['consentData'][$category] != "true"){
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Retrieve the Expiration DateTime stored in the consent cookie.
     *
     * This method extracts the Expiration DateTime from the consent cookie, if it exists. 
     * The DateTime is a plain text representation of a \DateTime object.
     *
     * @return string|null Returns the expiration datetime as a string if it exists in the consent cookie, or null if it doesn't.
     */
    public function getConsentExpiration(): ?string
    {
        return isset($this->getConsentCookie()['expiration']) ? $this->getConsentCookie()['expiration'] : null;
    }

    /**
     * Retrieve the DateTime stored in the consent cookie.
     *
     * This method extracts the DateTime from the consent cookie, if it exists. 
     * The DateTime is a plain text representation of a \DateTime object.
     *
     * @return string|null Returns the datetime as a string if it exists in the consent cookie, or null if it doesn't.
     */
    public function getConsentDateTime(): ?string
    {
        return isset($this->getConsentCookie()['datetime']) ? $this->getConsentCookie()['datetime'] : null;
    }

    /**
     * Retrieve the UUID stored in the consent cookie.
     *
     * This method extracts the UUID from the consent cookie, if it exists. 
     * The UUID is a unique identifier associated with the user's cookie consent record.
     *
     * @return string|null Returns the UUID as a string if it exists in the consent cookie, or null if it doesn't.
     */
    public function getConsentUuid(): ?string
    {
        return isset($this->getConsentCookie()['uuid']) ? $this->getConsentCookie()['uuid'] : null;
    }

    /**
     * Retrieve the consented categories from the consent cookie.
     *
     * This method extracts the array of categories for which the user has given consent,
     * as stored in the consent cookie.
     *
     * @return array Returns an associative array of consented categories if they exist in the consent cookie, or empty array if they don't.
     */
    public function getConsentCategories(): array
    {
        return isset($this->getConsentCookie()['consentData']) ? array_map(function ($arrayValue) { return filter_var($arrayValue, FILTER_VALIDATE_BOOLEAN); }, $this->getConsentCookie()['consentData']) : [];
    }

    /**
     * Retrieve the version of the consent stored in the consent cookie.
     *
     * This method extracts the version number of the consent data stored in the consent cookie.
     * The version helps determine if the consent given is for the current version of the cookie categories.
     *
     * @return int|null Returns the version number as an integer if it exists in the consent cookie, or null if it doesn't.
     */
    public function getConsentVersion(): ?int
    {
        return isset($this->getConsentCookie()['version']) ? $this->getConsentCookie()['version'] : null;
    }
}
