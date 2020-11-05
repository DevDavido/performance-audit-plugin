<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\PerformanceAudit;

require PIWIK_INCLUDE_PATH . '/plugins/PerformanceAudit/vendor/autoload.php';

use Exception;
use Piwik\Piwik;
use Piwik\Settings\FieldConfig;
use Piwik\Settings\Measurable\MeasurableSetting;
use Piwik\Settings\Measurable\MeasurableSettings as BaseMeasurableSettings;
use Piwik\Settings\Setting;
use Piwik\Validators\Exception as ValidatorException;

class MeasurableSettings extends BaseMeasurableSettings
{
    /** @var Setting */
    public $isEnabled;

    /** @var Setting */
    public $hasExtendedTimeout;

    /** @var Setting */
    public $runCount;

    /** @var Setting */
    public $emulatedDevice;

    /** @var Setting */
    public $hasGroupedUrls;

    /** @var Setting */
    public $hasExtraHttpHeader;

    /** @var Setting */
    public $extraHttpHeaderKey;

    /** @var Setting */
    public $extraHttpHeaderValue;

    /**
     * Initialise plugin settings.
     *
     * @return void
     * @throws ValidatorException|Exception
     */
    protected function init()
    {
        Piwik::checkUserHasSomeViewAccess();

        $this->isEnabled = $this->makeIsEnabledSetting();
        $this->hasExtendedTimeout = $this->makeHasExtendedTimeoutSetting();
        $this->runCount = $this->makeRunCountSetting();
        $this->emulatedDevice = $this->makeEmulatedDeviceSetting();
        $this->hasGroupedUrls = $this->makeHasGroupedUrlsSetting();
        $this->hasExtraHttpHeader = $this->makeHasExtraHttpHeaderSetting();
        $this->extraHttpHeaderKey = $this->makeExtraHttpHeaderKeySetting();
        $this->extraHttpHeaderValue = $this->makeExtraHttpHeaderValueSetting();
    }

    /**
     * Create is enabled setting.
     *
     * @return MeasurableSetting
     * @throws ValidatorException|Exception
     */
    private function makeIsEnabledSetting()
    {
        return $this->makeSetting('is_enabled', true, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = Piwik::translate('PerformanceAudit_Settings_IsEnabled_Title');
            $field->inlineHelp = Piwik::translate('PerformanceAudit_Settings_IsEnabled_Help');
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }

    /**
     * Create has extended timeout setting.
     *
     * @return MeasurableSetting
     * @throws ValidatorException|Exception
     */
    private function makeHasExtendedTimeoutSetting()
    {
        return $this->makeSetting('has_extended_timeout', false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = Piwik::translate('PerformanceAudit_Settings_HasExtendedTimeout_Title');
            $field->inlineHelp = Piwik::translate('PerformanceAudit_Settings_HasExtendedTimeout_Help');
            $field->condition = 'is_enabled';
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }

    /**
     * Create run count setting.
     *
     * @return MeasurableSetting
     * @throws ValidatorException|Exception
     */
    private function makeRunCountSetting()
    {
        return $this->makeSetting('run_count', 3, FieldConfig::TYPE_INT, function (FieldConfig $field) {
            $field->title = Piwik::translate('PerformanceAudit_Settings_RunCount_Title');
            $field->inlineHelp = Piwik::translate('PerformanceAudit_Settings_RunCount_Help');
            $field->condition = 'is_enabled';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->validate = function ($value) {
                if (empty($value) && $value != 0) {
                    throw new ValidatorException(Piwik::translate('General_ValidatorErrorEmptyValue'));
                }
                if ($value < 1 || $value > 5) {
                    throw new ValidatorException(Piwik::translate('PerformanceAudit_ValidatorRunCountOutOfRange'));
                }
            };
        });
    }

    /**
     * Create emulated device setting.
     *
     * @return MeasurableSetting
     * @throws ValidatorException|Exception
     */
    private function makeEmulatedDeviceSetting()
    {
        return $this->makeSetting('emulated_device', EmulatedDevice::__default, FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = Piwik::translate('PerformanceAudit_Settings_EmulatedDevice_Title');
            $field->inlineHelp = Piwik::translate('PerformanceAudit_Settings_EmulatedDevice_Help');
            $field->condition = 'is_enabled';
            $field->uiControl = FieldConfig::UI_CONTROL_SINGLE_SELECT;
            $field->availableValues = [
                EmulatedDevice::Desktop => ucfirst(Piwik::translate('PerformanceAudit_EnvironmentDesktop')),
                EmulatedDevice::Mobile => ucfirst(Piwik::translate('PerformanceAudit_EnvironmentMobile')),
                EmulatedDevice::Both => ucfirst(Piwik::translate('PerformanceAudit_EnvironmentDesktop')) . ' & ' . ucfirst(Piwik::translate('PerformanceAudit_EnvironmentMobile'))
            ];
            $field->validate = function ($value) use ($field) {
                if (empty($value)) {
                    throw new ValidatorException(Piwik::translate('General_ValidatorErrorEmptyValue'));
                }
                if (!in_array($value, array_keys($field->availableValues))) {
                    throw new ValidatorException(Piwik::translate('PerformanceAudit_ValidatorNotInSelect'));
                }
            };
        });
    }

    /**
     * Create a grouped URLs setting.
     *
     * @return MeasurableSetting
     * @throws ValidatorException|Exception
     */
    private function makeHasGroupedUrlsSetting()
    {
        return $this->makeSetting('has_grouped_urls', false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = Piwik::translate('PerformanceAudit_Settings_HasGroupedUrls_Title');
            $field->inlineHelp = Piwik::translate('PerformanceAudit_Settings_HasGroupedUrls_Help');
            $field->condition = 'is_enabled';
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }

    /**
     * Create has extra HTTP header setting.
     *
     * @return MeasurableSetting
     * @throws ValidatorException|Exception
     */
    private function makeHasExtraHttpHeaderSetting()
    {
        return $this->makeSetting('has_extra_http_header', false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = Piwik::translate('PerformanceAudit_Settings_HasExtraHttpHeader_Title');
            $field->inlineHelp = Piwik::translate('PerformanceAudit_Settings_HasExtraHttpHeader_Help');
            $field->condition = 'is_enabled';
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }

    /**
     * Create extra HTTP header setting.
     *
     * @return MeasurableSetting
     * @throws ValidatorException|Exception
     */
    private function makeExtraHttpHeaderKeySetting()
    {
        $self = $this;

        return $this->makeSetting('extra_http_header_key', '', FieldConfig::TYPE_STRING, function (FieldConfig $field) use ($self) {
            $field->title = Piwik::translate('PerformanceAudit_Settings_ExtraHttpHeaderKey_Title');
            $field->inlineHelp = Piwik::translate('PerformanceAudit_Settings_ExtraHttpHeaderKey_Help');
            $field->condition = 'is_enabled && has_extra_http_header';
            $field->uiControl = FieldConfig::UI_CONTROL_SINGLE_SELECT;
            $field->availableValues = [
                'Authorization' => Piwik::translate('PerformanceAudit_Settings_ExtraHttpHeaderKey_Authorization'),
                'Cookie' => Piwik::translate('PerformanceAudit_Settings_ExtraHttpHeaderKey_Cookie'),
            ];
            $field->validate = function ($value) use ($self, $field) {
                if ($self->hasExtraHttpHeader()) {
                    if (empty($value)) {
                        throw new ValidatorException(Piwik::translate('General_ValidatorErrorEmptyValue'));
                    }
                    if (!in_array($value, array_keys($field->availableValues))) {
                        throw new ValidatorException(Piwik::translate('PerformanceAudit_ValidatorNotInSelect'));
                    }
                }
            };
        });
    }

    /**
     * Create extra HTTP header value setting.
     *
     * @return MeasurableSetting
     * @throws ValidatorException|Exception
     */
    private function makeExtraHttpHeaderValueSetting()
    {
        $self = $this;

        return $this->makeSetting('extra_http_header_value', '', FieldConfig::TYPE_STRING, function (FieldConfig $field) use ($self) {
            $field->title = Piwik::translate('PerformanceAudit_Settings_ExtraHttpHeaderValue_Title');
            $field->inlineHelp = Piwik::translate('PerformanceAudit_Settings_ExtraHttpHeaderValue_Help');
            $field->condition = 'is_enabled && has_extra_http_header';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->validate = function ($value) use ($self, $field) {
                if ($self->hasExtraHttpHeader()) {
                    if (empty($value)) {
                        throw new ValidatorException(Piwik::translate('General_ValidatorErrorEmptyValue'));
                    }
                    if (!mb_check_encoding($value, 'ASCII') || strstr($value, PHP_EOL)) {
                        throw new ValidatorException(Piwik::translate('PerformanceAudit_ValidatorExtraHttpHeaderValueNotAscii'));
                    }
                }
            };
        });
    }

    /**
     * Returns array of runs of site.
     *
     * @return array
     */
    public function getRuns()
    {
        return range(1, (int) $this->getSetting('run_count')->getValue());
    }

    /**
     * Returns list of emulated devices of site.
     *
     * @return array
     */
    public function getEmulatedDevicesList()
    {
        return EmulatedDevice::getList($this->getSetting('emulated_device')->getValue());
    }

    /**
     * Returns if site has audits enabled or not.
     *
     * @return bool
     */
    public function isAuditEnabled()
    {
        return $this->getSetting('is_enabled')->getValue();
    }

    /**
     * Returns if site has extended timeout for site audit.
     *
     * @return bool
     */
    public function hasExtendedTimeout()
    {
        return $this->getSetting('has_extended_timeout')->getValue();
    }

    /**
     * Returns if site has grouped URLs for site.
     *
     * @return bool
     */
    public function hasGroupedUrls()
    {
        return $this->getSetting('has_grouped_urls')->getValue();
    }

    /**
     * Returns if site has extra HTTP header for site.
     *
     * @return bool
     */
    public function hasExtraHttpHeader()
    {
        return $this->getSetting('has_extra_http_header')->getValue();
    }
}
