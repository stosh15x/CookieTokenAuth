<?php

namespace Beskhue\CookieTokenAuth\Controller\Component;

use Cake\Controller\Component;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Routing\Router;

/**
 * Cookie token component.
 */
class CookieTokenComponent extends Component
{
    public $components = ['Cookie'];

    /**
     * Generates a new token cookie.
     * If $token is not given, generates a new series and token hash,
     * saves it, and sends the cookie to the user's browser.
     *
     * If $token is given, generates a new token hash (but uses the
     * same series as in $token), extends the expiration date, saves
     * it, and sends a new cookie to the user's browser.
     *
     * @param $user  The user data.
     * @param $token The token to re-use.
     */
    public function setCookie($user, $token = null)
    {
        $authTokens = \Cake\ORM\TableRegistry::get('Beskhue/CookieTokenAuth.AuthTokens', $this->_config);
        
        $expires = new \DateTime();
        $expires->modify($this->config()['cookie']['expires']);

        $series = hash('sha256', microtime(true).mt_rand());
        $t = hash('sha256', microtime(true).mt_rand());
        $tokenHash = (new DefaultPasswordHasher())->hash($t);


        if (!$token) {
            $token = $authTokens->newEntity();
            $token->user_id = $user['id'];
            $token->series = $series;
        }

        $token->token = $tokenHash;
        $token->expires = $expires;

        $this->Cookie->config([
            'path' => Router::url([
                'plugin' => 'Beskhue/CookieTokenAuth', 
                'controller' => 'CookieTokenAuth',
            ]),
            'encryption' => 'aes',
            'expires' => $this->config()['cookie']['expires'],
        ]);
        $this->Cookie->write($this->config()['cookie']['name'], [
            'series' => $token->series,
            'token' => $t,
        ]);

        $authTokens->save($token);
    }

    /**
     * Remove a token.
     *
     * @param $token The token to remove.
     */
    public function removeToken($token)
    {
        $this->delete($token);
    }

    /**
     * Remove the token cookie from the user's browser.
     *
     * Rewrites the cookie with dummy values and expires the cookie.
     */
    public function removeCookie()
    {
        $this->Cookie->config([
            'path' => Router::url([
                'plugin' => 'Beskhue/CookieTokenAuth', 
                'controller' => 'CookieTokenAuth',
                'prefix' => false,
            ]),
            'encryption' => 'aes',
            'expires' => '-1 day',
        ]);
        $this->Cookie->write($this->config()['cookie']['name'], []);
    }
}
