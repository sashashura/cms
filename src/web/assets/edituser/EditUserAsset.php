<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\edituser;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;

/**
 * Asset bundle for the Edit User page
 */
class EditUserAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/AccountSettingsForm.css',
        'css/profile.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'AccountSettingsForm.js',
        'profile.js',
        'webAuthn.js',
        'authForm.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('app', [
                'Copy the activation URL',
                'Copy the impersonation URL, and open it in a new private window.',
                'Please enter your current password.',
                'Please enter your password.',
                'Security key',
                'No security keys exist yet',
                'Waiting for a security key',
                'Waiting for elevated session',
                'Please enter a name for the security key',
                'Updating the authenticator settings',
            ]);
        }
    }
}
