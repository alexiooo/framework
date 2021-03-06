<?php

/**
 * @copyright Frederic G. Østby
 * @license   http://www.makoframework.com/license
 */

namespace mako\validator\rules\file;

use mako\http\request\UploadedFile;
use mako\validator\rules\Rule;
use mako\validator\rules\RuleInterface;

use function mb_strlen;
use function sprintf;

/**
 * Max filename length rule.
 *
 * @author Frederic G. Østby
 */
class MaxFilenameLength extends Rule implements RuleInterface
{
	/**
	 * Max filename length.
	 *
	 * @var int
	 */
	protected $maxLength;

	/**
	 * Constructor.
	 *
	 * @param int $maxLength Max filename length
	 */
	public function __construct(int $maxLength)
	{
		$this->maxLength = $maxLength;
	}

	/**
	 * I18n parameters.
	 *
	 * @var array
	 */
	protected $i18nParameters = ['maxLength'];

	/**
	 * {@inheritdoc}
	 */
	public function validate($value, array $input): bool
	{
		$filename = $value instanceof UploadedFile ? $value->getReportedFilename() : $value->getFilename();

		return mb_strlen($filename) <= $this->maxLength;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getErrorMessage(string $field): string
	{
		return sprintf('The %1$s filename must be at most %2$s characters long.', $field, $this->maxLength);
	}
}
