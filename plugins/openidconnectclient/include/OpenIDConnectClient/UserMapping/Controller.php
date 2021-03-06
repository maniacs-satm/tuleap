<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\OpenIDConnectClient\UserMapping;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;
use Tuleap\OpenIDConnectClient\Provider\ProviderNotFoundException;
use UserManager;

class Controller {

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var ProviderManager
     */
    private $provider_manager;

    /**
     * @var UserMappingManager
     */
    private $user_mapping_manager;

    public function __construct(
        UserManager $user_manager,
        ProviderManager $provider_manager,
        UserMappingManager $user_mapping_manager
    ) {
        $this->user_manager         = $user_manager;
        $this->provider_manager     = $provider_manager;
        $this->user_mapping_manager = $user_mapping_manager;
    }

    public function removeMapping($provider_id) {
        $csrf_token = new CSRFSynchronizerToken('openid-connect-user-preferences');
        $csrf_token->check('/account/');

        try {
            $provider = $this->provider_manager->getById($provider_id);
        } catch (ProviderNotFoundException $ex) {
            $this->redirectToAccountPage(
                $GLOBALS['Language']->getText('plugin_openidconnectclient', 'invalid_request'),
                Feedback::ERROR
            );
        }
        try {
            $this->user_mapping_manager->removeByUserAndProvider(
                $this->user_manager->getCurrentUser(),
                $provider
            );
            $this->redirectToAccountPage(
                $GLOBALS['Language']->getText(
                    'plugin_openidconnectclient',
                    'delete_user_mapping_success',
                    array($provider->getName())
                ),
                Feedback::INFO
            );
        } catch (UserMappingDataAccessException $ex) {
            $this->redirectToAccountPage(
                $GLOBALS['Language']->getText(
                    'plugin_openidconnectclient',
                    'delete_user_mapping_error',
                    array($provider->getName())
                ),
                Feedback::ERROR
            );

        }
    }

    private function redirectToAccountPage($message, $feedback_type) {
        $GLOBALS['Response']->addFeedback(
            $feedback_type,
            $message
        );
        $GLOBALS['Response']->redirect('/account/');
    }

}