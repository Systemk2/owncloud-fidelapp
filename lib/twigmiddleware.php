<?php


namespace OCA\FidelApp;

//use OCA\AppFramework\Controller\Controller;
use OCA\AppFramework\Http\Response;
use OCA\AppFramework\Http\TemplateResponse;
use OCA\AppFramework\Middleware\Middleware;
use OCA\FidelApp\TwigResponse;
use OCA\FidelApp\API;


/**
 * This template is used to add the possibility to add twig templates
 * By default it is only loaded when the templatepath is set
 */
class TwigMiddleware extends Middleware {

	private $twig;
	private $api;
	private $renderAs;

	/**
	 * Sets the twig loader instance
	 * @param API $api an instance of the api
	 * @param Twig_Environment $twig an instance of the twig environment
	 */
	public function __construct(API $api, $twig){
		$this->api = $api;
		$this->twig = $twig;
	}


	/**
	 * Swaps the template response with the twig response and stores if a
	 * template needs to be printed for the user or admin page
	 *
	 * @param Controller $controller the controller that is being called
	 * @param string $methodName the name of the method that will be called on
	 *                           the controller
	 * @param Response $response the generated response from the controller
	 * @return Response a Response object
	 */
	public function afterController($controller, $methodName, Response $response){
		if($response instanceof TemplateResponse){
			$this->renderAs = $response->getRenderAs();

			$twigResponse = new TwigResponse(
				$this->api,
				$response->getTemplateName(),
				$this->twig
			);

			foreach($response->getHeaders() as $name => $value){
				$twigResponse->addHeader($name, $value);
			}

			$twigResponse->setParams($response->getParams());
			return $twigResponse;
		} else {
			$this->renderAs = 'blank';
			return $response;
		}
	}


	/**
	 * In case the output is not rendered as blank page, we need to include the
	 * owncloud header and output
	 * @param Controller $controller the controller that is being called
	 * @param string $methodName the name of the method that will be called on
	 *                           the controller
	 * @param string $output the generated output from a response
	 * @return string the output that should be printed
	 */
	public function beforeOutput($controller, $methodName, $output){
		if($this->renderAs === 'blank'){
			return $output;
		} else {
			$template = $this->api->getTemplate(
				'twig',
				$this->renderAs,
				'appframework'
			);

			$template->assign('twig', $output, false);
			$output = $template->fetchPage();

			return $output;
		}
	}

}
