<?php

namespace Eveltic\CookieBundle\Controller;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Eveltic\CookieBundle\Service\CookieConsentService;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller to handle requests related to cookie consent.
 */
#[AsController]
class CookieConsentController
{
    /**
     * Constructor for the CookieConsentListener class.
     *
     * @param CookieConsentService $cookieConsentService 
     *        The service responsible for handling all operations related to cookie consent, including checking, saving, and retracting consent.
     *
     * @param LoggerInterface $logger 
     *        The logger interface used for logging events, errors, and other relevant information related to cookie consent.
     *
     * @param RequestStack $requestStack 
     *        Provides access to the current request and session data, which is useful for determining the user's consent status and managing request-level data.
     */
    public function __construct(
        private CookieConsentService $cookieConsentService, 
        private LoggerInterface $logger,
        private RequestStack $requestStack
    ){}

    /**
     * Handle the POST request to save user cookie consent.
     *
     * @param Request $request
     * @return Response
     */
    #[Route('/cookie-consent', name: 'cookie_consent', methods: ['POST'])]
    public function consent(): JsonResponse
    {
        $response = new JsonResponse();
        $request = $this->requestStack->getMainRequest();
        try {
            $consentData = $request->request->all();
            // Set required categories as true
            $categories = array_combine(array_column($this->cookieConsentService->getCategories(), 'name'), $this->cookieConsentService->getCategories());
            foreach($consentData as $consentCategory => $acceptance){
                $consentData[$consentCategory] = (isset($categories[$consentCategory]) AND $categories[$consentCategory]['required'] === true) ? "true" : $acceptance;
            }

            $ip = $request->getClientIp();

            $cookieValue = $this->cookieConsentService->saveConsent($consentData, $response, $ip, $request->server->get('HTTP_USER_AGENT', null));

            return $response->setData(['status' => 'success', 'cookieValue' => $cookieValue]);
        } catch (Exception $e) {
            $this->logger->error('Error processing cookie consent: ' . $e->getMessage());

            return $response->setData([
                'status' => 'error',
                'message' => 'An error occurred while processing your request. Please try again later.'
            ])->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Handle the POST request to retract user cookie consent.
     *
     * @param Request $request
     * @return Response
     */
    #[Route('/retract-consent', name: 'retract_consent', methods: ['POST'])]
    public function retractConsent(): JsonResponse
    {
        $response = new JsonResponse();
        try {
            $this->cookieConsentService->retractConsent($response);
            return $response->setData(['status' => 'success']);
        } catch (Exception $e) {
            $this->logger->error('Error retracting cookie consent: ' . $e->getMessage());

            return $response->setData([
                'status' => 'error',
                'message' => 'An error occurred while processing your request. Please try again later.'
            ])->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
