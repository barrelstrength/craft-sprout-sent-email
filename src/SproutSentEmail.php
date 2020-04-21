<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com/
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   http://sprout.barrelstrengthdesign.com/license
 */

namespace barrelstrength\sproutsentemail;

use barrelstrength\sproutbase\base\SproutDependencyInterface;
use barrelstrength\sproutbase\base\SproutDependencyTrait;
use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutbasesentemail\models\Settings as SproutBaseSentEmailSettings;
use barrelstrength\sproutbasesentemail\SproutBaseSentEmail;
use Craft;
use craft\base\Plugin;
use craft\db\Query;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use yii\base\Event;
use yii\web\Response;

/**
 * @property mixed                                                    $cpNavItem
 * @property array                                                    $cpUrlRules
 * @property array                                                    $userPermissions
 * @property array                                                    $sproutDependencies
 * @property \yii\console\Response|\craft\web\Response|Response|mixed $settingsResponse
 * @property array                                                    $siteUrlRules
 */
class SproutSentEmail extends Plugin implements SproutDependencyInterface
{
    use SproutDependencyTrait;

    const EDITION_LITE = 'lite';
    const EDITION_PRO = 'pro';

    /**
     * @var bool
     */
    public $hasCpSection = true;

    /**
     * @var bool
     */
    public $hasCpSettings = true;

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * @inheritdoc
     */
    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            self::EDITION_PRO,
        ];
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        Craft::setAlias('@sproutsentemail', $this->getBasePath());

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, $this->getCpUrlRules());
        });

        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $event->permissions['Sprout Sent Email'] = $this->getUserPermissions();
        });
    }

    public function getCpNavItem()
    {
        $parent = parent::getCpNavItem();

        $settings = $this->getSettings();

        // Allow user to override plugin name in sidebar
        if ($settings->pluginNameOverride) {
            $parent['label'] = $settings['pluginNameOverride'];
        }
        if (Craft::$app->getUser()->checkPermission('sproutSentEmail-viewSentEmail') && $settings->enableSentEmails) {
            $parent['subnav']['sentemails'] = [
                'label' => Craft::t('sprout-email', 'Sent Emails'),
                'url' => 'sprout-sent-email/sent-email'
            ];
        }

        if (Craft::$app->getUser()->getIsAdmin()) {
            $parent['subnav']['settings'] = [
                'label' => Craft::t('sprout-sent-email', 'Settings'),
                'url' => 'sprout-sent-email/settings'
            ];
        }

        return $parent;
    }

    /**
     * @return array
     */
    public function getUserPermissions(): array
    {
        return [
            // We need this permission on top of the accessplugin- permission
            // so that we can support the matching permission in Sprout Email
            'sproutSentEmail-viewSentEmail' => [
                'label' => Craft::t('sprout-sent-email', 'View Sent Email'),
                'nested' => [
                    'sproutSentEmail-resendEmails' => [
                        'label' => Craft::t('sprout-sent-email', 'Resend Sent Emails')
                    ]
                ]
            ],
        ];
    }

    /**
     * @return array
     */
    public function getSproutDependencies(): array
    {
        return [
            SproutDependencyInterface::SPROUT_BASE,
            SproutDependencyInterface::SPROUT_BASE_EMAIL
        ];
    }

    /**
     * @return SproutBaseSentEmailSettings
     */
    protected function createSettingsModel(): SproutBaseSentEmailSettings
    {
        /** @var SproutBaseSentEmailSettings $settingsModel */
        $settingsModel = SproutBase::$app->settings->getBaseSettings(SproutBaseSentEmailSettings::class, $this->handle);

        return $settingsModel;
    }

    /**
     * Redirect to Sprout Sitemaps settings
     *
     * @return \craft\web\Response|mixed|\yii\console\Response|Response
     */
    public function getSettingsResponse()
    {
        $url = UrlHelper::cpUrl('sprout-sent-email/settings');

        return Craft::$app->getResponse()->redirect($url);
    }

    /**
     * @return array
     */
    private function getCpUrlRules(): array
    {
        return [
            // Sent Emails
            'sprout-sent-email/sent-email' => [
                'template' => 'sprout-base-sent-email/sent-email/index'
            ],

            // Settings
            'sprout-sent-email/settings/<settingsSectionHandle:.*>' => [
                'route' => 'sprout/settings/edit-settings',
                'params' => [
                    'sproutBaseSettingsType' => SproutBaseSentEmailSettings::class
                ]
            ],
            'sprout-sent-email/settings' => [
                'route' => 'sprout/settings/edit-settings',
                'params' => [
                    'sproutBaseSettingsType' => SproutBaseSentEmailSettings::class
                ]
            ]
        ];
    }
}