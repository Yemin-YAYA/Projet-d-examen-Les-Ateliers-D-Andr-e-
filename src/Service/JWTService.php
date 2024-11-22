<?php
namespace App\Service;

use DateTimeZone;
use DateTimeImmutable;

class JWTService
{
    // On génère le token

    /**
     * Génération du JWT
     * @param array $header 
     * @param array $payload 
     * @param string $secret 
     * @param int $validity 
     * @return string 
     */
    public function generate(array $header, array $payload, string $secret, int $validity = 900): string
    {
        if($validity > 0){
            $now = new DateTimeImmutable();
            $exp = $now->getTimestamp() + $validity;
    
            $payload['iat'] = $now->getTimestamp();
            $payload['exp'] = $exp;
        }

        // On encode en base64
        $base64Header = base64_encode(json_encode($header));
        $base64Payload = base64_encode(json_encode($payload));

        // On "nettoie" les valeurs encodées (retrait des +, / et =)
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], $base64Header);
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], $base64Payload);

        // On génère la signature
        $secret = base64_encode($secret);
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);

        $base64Signature = base64_encode($signature);

        $signature = str_replace(['+', '/', '='], ['-', '_', ''], $base64Signature);

        // On crée le token
        $jwt = $base64Header . '.' . $base64Payload . '.' . $signature;

        return $jwt;
    }

    //On vérifie que le token est valide (correctement formé)
    public function isValid(string $token): bool
    {
        return preg_match(
            '/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/',
            $token
        ) === 1;
    }

    // On récupère le Payload
    public function getPayload(string $token): array
    {
        // On démonte le token
        $array = explode('.', $token);

        // On décode le Payload
        $payload = json_decode(base64_decode($array[1]), true);

        return $payload;
    }

    // On récupère le Header
    public function getHeader(string $token): array
    {
        // On démonte le token
        $array = explode('.', $token);

        // On décode le Header
        $header = json_decode(base64_decode($array[0]), true);

        return $header;
    }

    // On vérifie si le token a expiré
    public function isExpired(string $token): bool
    {
        $payload = $this->getPayload($token); // Récupère le payload du token
    
        if (isset($payload['exp'])) {
            $now = (new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')))->getTimestamp();
            dump($payload['exp'], $now); // Debugging : affiche l'expiration et le timestamp actuel
            return $payload['exp'] < $now; // Retourne true si expiré
        }
    
        return true; // Considérer comme expiré si le champ 'exp' n'existe pas
    }

    // On vérifie la signature du Token
    public function check(string $token, string $secret): bool
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false; // Format invalide
        }
    
        [$base64Header, $base64Payload, $providedSignature] = $parts;
    
        // Nettoyage des valeurs encodées
        $base64Header = str_replace(['-', '_'], ['+', '/'], $base64Header);
        $base64Payload = str_replace(['-', '_'], ['+', '/'], $base64Payload);
        $providedSignature = str_replace(['-', '_'], ['+', '/'], $providedSignature);
    
        // Calcul de la signature
        $secret = base64_encode($secret);
        $calculatedSignature = base64_encode(
            hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true)
        );
    
        // Vérification de la signature
        return hash_equals($providedSignature, $calculatedSignature);
    }
}