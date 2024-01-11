<?php


namespace App\Controllers;


use finfo;
use Selective\Config\Configuration;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Slim\Views\Twig;

/**
 * Class UploadsController
 * @package App\Controller
 *
 * @property Twig view
 */
class UploadsController extends Controller
{


	public function getFile(ServerRequest $request, Response $response, $args = [])
	{
		$dir  = $this->container->get(Configuration::class)->getString("storage");
		$file = @file_get_contents($dir . '/' . $args['path']);

		if ($file) {
			$file_info = new finfo(FILEINFO_MIME_TYPE);
			$mime_type = $file_info->buffer($file);

			return $response->withHeader('Content-type', $mime_type)->write($file);
		}
		return $response;
	}
	public function getSimpleFile(ServerRequest $request, Response $response, $args = [])
	{
		$dir  = $this->container->get(Configuration::class)->getString("storage");
		$file = @file_get_contents($dir . $request->getRequestTarget() . '.pdf');

		if ($file) {
			$file_info = new finfo(FILEINFO_MIME_TYPE);
			$mime_type = $file_info->buffer($file);

			return $response->withHeader('Content-type', $mime_type)->write($file);
		}
		return $response;
	}

	public function formHelper(ServerRequest $request, Response $response, $args = [])
	{
		return $response->withRedirect("https://docs.google.com/forms/d/e/1FAIpQLScXkeKGFbCrp0jDfS1JzVRDSnq_0lGieXp3WZYpw9zavpZ4mg/viewform");
	}


//	public function test(ServerRequest $request, Response $response, $args = [])
//	{
//		$options = [
//			// default values
//			// 'indentation' => '    ', // 4 spaces
//		];
//		$css     = new CSS_Generator($options);
//
//// single selector
//		$css->add_rule('.color-white', ['color' => '#fff']);
//
//		$css->open_block('media', 'screen and (min-width: 30em)');
//
//// multiple selectors
//		$css->add_rule(['html', 'body'], [
//			'background-color' => 'black',
//			'color'            => 'white'
//		]);
//
//		$css->close_block(); // close a block
//
//		$css->open_block('supports', '(display: grid)');
//
//		$css->add_rule('.grid', [
//			'display' => 'grid',
//		]);
//
//// nested block
//		$css->open_block('media', 'screen and (max-width: 30em)');
//
//		$css->add_rule('.grid-sm', [
//			'display' => 'grid',
//		]);
//
//		$css->close_blocks(); // close all blocks
//
//		return $response->withHeader('Content-type', "text/css")->write($css->get_output(true));
//
//	}


}