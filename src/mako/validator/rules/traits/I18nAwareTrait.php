<?php

/**
 * @copyright Frederic G. Østby
 * @license   http://www.makoframework.com/license
 */

namespace mako\validator\rules\traits;

use mako\i18n\I18n;
use mako\validator\rules\RuleInterface;
use mako\validator\rules\WithParametersInterface;

use function array_map;
use function array_merge;
use function array_values;
use function implode;
use function is_array;
use function property_exists;
use function str_replace;

/**
 * I18n aware trait.
 *
 * @author Frederic G. Østby
 */
trait I18nAwareTrait
{
	/**
	 * I18n.
	 *
	 * @var \mako\i18n\I18n
	 */
	protected $i18n;

	/**
	 * {@inheritdoc}
	 */
	public function setI18n(I18n $i18n): RuleInterface
	{
		$this->i18n = $i18n;

		return $this;
	}

	/**
	 * Returns a translated field name.
	 *
	 * @param  string $field   Field name
	 * @param  string $package Package prefix
	 * @return string
	 */
	protected function translateFieldName(string $field, string $package): string
	{
		// Return custom field name if we have one

		if($this->i18n->has(($i18nKey = $package . 'validate.overrides.fieldnames.' . $field)))
		{
			return $this->i18n->get($i18nKey);
		}

		// Return field a more human friendly field name

		return str_replace('_', ' ', $field);
	}

	/**
	 * Gets the i18n parameters.
	 *
	 * @param  string $field   Field name
	 * @param  string $package Package prefix
	 * @return array
	 */
	protected function getI18nParameters(string $field, string $package): array
	{
		if($this instanceof WithParametersInterface)
		{
			$parameters = array_map(function($value)
			{
				return is_array($value) ? implode(', ', $value) : $value;
			}, $this->parameters);

			if(property_exists($this, 'i18nFieldNameParameters'))
			{
				foreach($this->i18nFieldNameParameters as $i18nField)
				{
					$parameters[$i18nField] = $this->translateFieldName($parameters[$i18nField], $package);
				}
			}

			return array_merge([$field], array_values($parameters));
		}

		return [$field];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getTranslatedErrorMessage(string $field, string $rule, string $package = null): string
	{
		$rule = str_replace(($package = empty($package) ? '' : ($package . '::')), '', $rule);

		// Return custom error message if we have one

		if($this->i18n->has(($i18nKey = $package . 'validate.overrides.messages.' . $field . '.' . $rule)))
		{
			return $this->i18n->get($i18nKey, $this->getI18nParameters($field, $package));
		}

		// Attempt to translate the field name

		$field = $this->translateFieldName($field, $package);

		// Return default error message from language file if we have one

		if($this->i18n->has(($i18nKey = $package . 'validate.' . $rule)))
		{
			return $this->i18n->get($package . 'validate.' . $rule, $this->getI18nParameters($field, $package));
		}

		// Return default error message from rule

		return $this->getErrorMessage($field);
	}
}
