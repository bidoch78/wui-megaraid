<?php 

declare(strict_types=1);

namespace megaraid\controllers;

use \megaraid\exceptions\megaraidException;
use \megaraid\exceptions\authTokenException;
use \megaraid\controllers\controller;
use \megaraid\database\table;
use \megaraid\database\token as dbToken;
use \megaraid\database\user as dbUser;
use \megaraid\http;
use \megaraid\core;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class token extends controller {

    public static function removeExpiredToken() {
        dbToken::removeExpiredToken();
    }

    public static function generateTokens(dbUser $user, array $options = null): array {

        $removeExpToken = (!$options || (isset($options["removeexpiredtokens"]) && $options["removeexpiredtokens"] === true)) ? true: false;

        if ($removeExpToken) dbToken::removeExpiredToken();

        $data = [];

        $currentDate = table::getUTCNow();
        
/*
https://www.iana.org/assignments/jwt/jwt.xhtml

{
    "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJmaXJzdE5hbWUiOiJKb2huIiwibGFzdE5hbWUiOiJEb2UiLCJpYXQiOjE1OTE3MDcwMTksImV4cCI6MTU5MTcxMDYxOSwiYXVkIjoiIiwiaXNzIjoiIiwic3ViIjoiMSJ9.Nw41AQAiG7Qq0AVPFgY9H4-zG-4-JcUZ4KA3pFgMzPg",
    "tokenType": "Bearer",
    "accessTokenExpiresIn": 3600000,
    "refreshToken": "CZzSZh2KbSURsmnhs8cRSLg0u87hPvINi8GjSQ7CQ2oGvx/GdEmBKzds0pr/r7tSI3Of1DIGBaL1P2xCOV3ynX/dFz8MblViF8BOrQROSRVMKhBwMyAY7akxQrFqpTeWN/abtEVSkTP7tlUUcI8PTjlY9ZPSakG2nBvPbf+fgM8=",
    "refreshTokenExpiresIn": 2592000000

    « iss » (Issuer) : Permet d’identifier le serveur ou le système qui a émis le token;
« sub » (Subject) :  Il s’agit généralement de l’identifiant de l’utilisateur que le token représente;
« aud » (Audience) : Il s’agit généralement de l’application ou du site qui reçoit le token;
« iat »  (Issued At) : Il s’agit de la date de génération du token;
« exp » (Expiration Time) : Il s’agit de la date d’expiration du token.<z

 "iss":"https://codeheroes.fr",
 "iat":1521309600,
 "exp": 1521313200,
        1731864712
 "aud":"https://siteclient.fr",
 "sub":"124",
 "role":"user"
 
}

usually validate that nbf <= now <= exp. So nbf is offset to the past to address potential issues with non time-synchronised systems. And iat is included just in case you need to know when the token was issued (those are my guesses, but based on years of experience)

*/

        $xsrf = self::getXsrf();

        $data["auth"] = [
            "accessTokenExpiresIn" => self::getAccessTokenLifetime(),
            "refreshTokenExpiresIn" => self::getRefreshTokenLifetime(),
            "xsrfToken" => $xsrf
        ];

        $payloadRefreshToken = [
            'iss' => core::env("APP_URL", "www.wmui.com"),
            'aud' => '-',
            'jti' => dbToken::generateToken(),
            'iat' => $currentDate->getTimeStamp(),
            'nbf' => $currentDate->getTimeStamp(),
            'exp' => $currentDate->getTimeStamp() + self::getRefreshTokenLifetime(),
            'xcrf' => $xsrf
        ];

        $payloadAccessToken = [
            'iss' => core::env("APP_URL", "www.wmui.com"),
            'aud' => '-',
            'jti' => dbToken::generateToken(),
            'iat' => $currentDate->getTimeStamp(),
            'nbf' => $currentDate->getTimeStamp(),
            'exp' => $currentDate->getTimeStamp() + self::getAccessTokenLifetime(),
            'sub' => $user->id,
            'xcrf' => $xsrf            
        ];

        if ($options && isset($options["copyrefreshtokentime"]) && $options["copyrefreshtokentime"] === true) {


        }

        setcookie("access_token", JWT::encode($payloadAccessToken, self::getSecretKey(), 'HS256'), 0, "/api/", "", http::isHTTPS(), true);
        //setcookie("access_token", JWT::encode($payloadAccessToken, self::getSecretKey(), 'HS256'), $payloadAccessToken["exp"], "/api/", "", http::isHTTPS(), true);
        setcookie("refresh_token",JWT::encode($payloadRefreshToken, self::getSecretKey(), 'HS256'), $payloadRefreshToken["exp"], "/api/", "", http::isHTTPS(), true);

        try {
            
            //Create AccessToken
            $payloadDate = clone $currentDate;
            $payloadDate->add(new \DateInterval("PT" . self::getRefreshTokenLifetime() . "S"));

            dbToken::create($payloadRefreshToken["jti"], $currentDate, $payloadDate, $user->id);

        }
        catch(\Exception $ex) {
            throw new megaraidException("Error during token generation (" . $ex->getMessage() . ")", megaraidException::TOKEN_GENERATOR);
        }

        return $data;

    }

    public static function generateAccessToken(array $refreshtoken, int &$userid): null|array {

        /*
            when we create a new refreshtoken we need to keep the same experied time
        */

        if (!isset($refreshtoken["jti"])) return null;

        token::removeExpiredToken();

        $tokenInfo = dbToken::getByToken($refreshtoken["jti"]);
        if (!$tokenInfo) return null;

        //Generate new Access Token
        $user = dbUser::getById($tokenInfo->user_id);
        if (!$user) return null;
        $userid = $user->id;

        //Remove previous refreshtoken
        $tokenInfo->delete();

        return self::generateTokens($user, [ "removeexpiredtokens" => false, "currentrefreshtoken" => $refreshtoken, "copyrefreshtokentime" => true ]);

    }

    public static function getPayload(string $jwt):null|array {

        try {
           return (array)JWT::decode($jwt, new Key(self::getSecretKey(), 'HS256'));
        }
        catch(\Exception $ex) {
            //No error
        }

        return null;

    }

    public static function getAccessTokenLifetime():int {
        $val = core::env("access_token_lifetime");
        return is_numeric($val) ? intval($val) : 1800;
        //return is_numeric($val) ? intval($val) : 10;
    }

    public static function getRefreshTokenLifetime():int {
        $val = core::env("refresh_token_lifetime");
        return is_numeric($val) ? intval($val) : 86400;
        //return is_numeric($val) ? intval($val) : 12;
    }

    public static function getSecretKey(): string {
        return core::env("token_secret", "FY3o5KiPA2yMTHUz9mCHpUbjxoRatb3k27+lyWNxVkg=");
    }

    public static function getXsrf(): string {
        return "mwui_" . bin2hex(random_bytes(32));
    }

}

?>