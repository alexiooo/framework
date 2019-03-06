<?php

/**
 * @copyright Frederic G. Østby
 * @license   http://www.makoframework.com/license
 */

namespace mako\tests\unit\http\response\builders;

use mako\http\response\Headers;
use mako\http\response\senders\Redirect;
use mako\tests\TestCase;
use Mockery;

/**
 * @group unit
 */
class RedirectTest extends TestCase
{
	/**
	 *
	 */
	public function testSend(): void
	{
		$request = Mockery::mock('mako\http\Request');

		$responseHeaders = Mockery::mock(Headers::class);

		$responseHeaders->shouldReceive('add')->once()->with('Location', 'http://example.org');

		$response = Mockery::mock('mako\http\Response');

		$response->shouldReceive('setStatus')->once()->with(302);

		$response->shouldReceive('getHeaders')->once()->andReturn($responseHeaders);

		$response->shouldReceive('sendHeaders')->once();

		//

		$redirect = new Redirect('http://example.org');

		$redirect->send($request, $response);
	}

	/**
	 *
	 */
	public function testSendWithConstructorStatus(): void
	{
		$request = Mockery::mock('mako\http\Request');

		$responseHeaders = Mockery::mock(Headers::class);

		$responseHeaders->shouldReceive('add')->once()->with('Location', 'http://example.org');

		$response = Mockery::mock('mako\http\Response');

		$response->shouldReceive('setStatus')->once()->with(304);

		$response->shouldReceive('getHeaders')->once()->andReturn($responseHeaders);

		$response->shouldReceive('sendHeaders')->once();

		//

		$redirect = new Redirect('http://example.org', 304);

		$redirect->send($request, $response);
	}

	/**
	 *
	 */
	public function testSendWithStatus(): void
	{
		$request = Mockery::mock('mako\http\Request');

		$responseHeaders = Mockery::mock(Headers::class);

		$responseHeaders->shouldReceive('add')->once()->with('Location', 'http://example.org');

		$response = Mockery::mock('mako\http\Response');

		$response->shouldReceive('setStatus')->once()->with(304);

		$response->shouldReceive('getHeaders')->once()->andReturn($responseHeaders);

		$response->shouldReceive('sendHeaders')->once();

		//

		$redirect = new Redirect('http://example.org');

		$redirect->setStatus(304);

		$redirect->send($request, $response);
	}

	/**
	 *
	 */
	public function testSendWithStatus300(): void
	{
		$request = Mockery::mock('mako\http\Request');

		$responseHeaders = Mockery::mock(Headers::class);

		$responseHeaders->shouldReceive('add')->once()->with('Location', 'http://example.org');

		$response = Mockery::mock('mako\http\Response');

		$response->shouldReceive('setStatus')->once()->with(300);

		$response->shouldReceive('getHeaders')->once()->andReturn($responseHeaders);

		$response->shouldReceive('sendHeaders')->once();

		//

		$redirect = new Redirect('http://example.org');

		$redirect->multipleChoices();

		$redirect->send($request, $response);
	}

	/**
	 *
	 */
	public function testSendWithStatus301(): void
	{
		$request = Mockery::mock('mako\http\Request');

		$responseHeaders = Mockery::mock(Headers::class);

		$responseHeaders->shouldReceive('add')->once()->with('Location', 'http://example.org');

		$response = Mockery::mock('mako\http\Response');

		$response->shouldReceive('setStatus')->once()->with(301);

		$response->shouldReceive('getHeaders')->once()->andReturn($responseHeaders);

		$response->shouldReceive('sendHeaders')->once();

		//

		$redirect = new Redirect('http://example.org');

		$redirect->movedPermanently();

		$redirect->send($request, $response);
	}

	/**
	 *
	 */
	public function testSendWithStatus302(): void
	{
		$request = Mockery::mock('mako\http\Request');

		$responseHeaders = Mockery::mock(Headers::class);

		$responseHeaders->shouldReceive('add')->once()->with('Location', 'http://example.org');

		$response = Mockery::mock('mako\http\Response');

		$response->shouldReceive('setStatus')->once()->with(302);

		$response->shouldReceive('getHeaders')->once()->andReturn($responseHeaders);

		$response->shouldReceive('sendHeaders')->once();

		//

		$redirect = new Redirect('http://example.org');

		$redirect->found();

		$redirect->send($request, $response);
	}

	/**
	 *
	 */
	public function testSendWithStatus303(): void
	{
		$request = Mockery::mock('mako\http\Request');

		$responseHeaders = Mockery::mock(Headers::class);

		$responseHeaders->shouldReceive('add')->once()->with('Location', 'http://example.org');

		$response = Mockery::mock('mako\http\Response');

		$response->shouldReceive('setStatus')->once()->with(303);

		$response->shouldReceive('getHeaders')->once()->andReturn($responseHeaders);

		$response->shouldReceive('sendHeaders')->once();

		//

		$redirect = new Redirect('http://example.org');

		$redirect->seeOther();

		$redirect->send($request, $response);
	}

	/**
	 *
	 */
	public function testSendWithStatus304(): void
	{
		$request = Mockery::mock('mako\http\Request');

		$responseHeaders = Mockery::mock(Headers::class);

		$responseHeaders->shouldReceive('add')->once()->with('Location', 'http://example.org');

		$response = Mockery::mock('mako\http\Response');

		$response->shouldReceive('setStatus')->once()->with(304);

		$response->shouldReceive('getHeaders')->once()->andReturn($responseHeaders);

		$response->shouldReceive('sendHeaders')->once();

		//

		$redirect = new Redirect('http://example.org');

		$redirect->notModified();

		$redirect->send($request, $response);
	}

	/**
	 *
	 */
	public function testSendWithStatus305(): void
	{
		$request = Mockery::mock('mako\http\Request');

		$responseHeaders = Mockery::mock(Headers::class);

		$responseHeaders->shouldReceive('add')->once()->with('Location', 'http://example.org');

		$response = Mockery::mock('mako\http\Response');

		$response->shouldReceive('setStatus')->once()->with(305);

		$response->shouldReceive('getHeaders')->once()->andReturn($responseHeaders);

		$response->shouldReceive('sendHeaders')->once();

		//

		$redirect = new Redirect('http://example.org');

		$redirect->useProxy();

		$redirect->send($request, $response);
	}

	/**
	 *
	 */
	public function testSendWithStatus307(): void
	{
		$request = Mockery::mock('mako\http\Request');

		$responseHeaders = Mockery::mock(Headers::class);

		$responseHeaders->shouldReceive('add')->once()->with('Location', 'http://example.org');

		$response = Mockery::mock('mako\http\Response');

		$response->shouldReceive('setStatus')->once()->with(307);

		$response->shouldReceive('getHeaders')->once()->andReturn($responseHeaders);

		$response->shouldReceive('sendHeaders')->once();

		//

		$redirect = new Redirect('http://example.org');

		$redirect->temporaryRedirect();

		$redirect->send($request, $response);
	}

	/**
	 *
	 */
	public function testSendWithStatus308(): void
	{
		$request = Mockery::mock('mako\http\Request');

		$responseHeaders = Mockery::mock(Headers::class);

		$responseHeaders->shouldReceive('add')->once()->with('Location', 'http://example.org');

		$response = Mockery::mock('mako\http\Response');

		$response->shouldReceive('setStatus')->once()->with(308);

		$response->shouldReceive('getHeaders')->once()->andReturn($responseHeaders);

		$response->shouldReceive('sendHeaders')->once();

		//

		$redirect = new Redirect('http://example.org');

		$redirect->permanentRedirect();

		$redirect->send($request, $response);
	}
}
