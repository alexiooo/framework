<?php

/**
 * @copyright Frederic G. Østby
 * @license   http://www.makoframework.com/license
 */

namespace mako\tests\unit\validator\rules;

use mako\tests\TestCase;
use mako\validator\rules\Match;

/**
 * @group unit
 */
class MatchTest extends TestCase
{
	/**
	 *
	 */
	public function testValidatesWhenEmpty(): void
	{
		$rule = new Match('barfoo');

		$this->assertFalse($rule->validateWhenEmpty());
	}

	/**
	 *
	 */
	public function testWithValidValue(): void
	{
		$rule = new Match('barfoo');

		$this->assertTrue($rule->validate('bar', ['barfoo' => 'bar']));
	}

	/**
	 *
	 */
	public function testWithInvalidValue(): void
	{
		$rule = new Match('barfoo');

		$this->assertFalse($rule->validate('foo', ['barfoo' => 'bar']));

		$this->assertSame('The values of the foobar field and barfoo field must match.', $rule->getErrorMessage('foobar'));
	}
}
