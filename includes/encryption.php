<?php
class CustomEncryption {
    private $userAlgorithm;
    private $masterKey;
    
    public function __construct($userId, $masterPassword) {
        $this->masterKey = hash('sha512', $masterPassword . PEPPER);
        $this->userAlgorithm = $this->generateUserAlgorithm($userId, $masterPassword);
    }
    
    // توليد خوارزمية تشفير فريدة لكل مستخدم
    private function generateUserAlgorithm($userId, $masterPassword) {
        $seed = hash('sha512', $userId . $masterPassword . PEPPER);
        $algorithm = [
            'primary' => 'AES-256-CBC',
            'secondary' => 'BF-CBC',
            'iterations' => (hexdec(substr($seed, 0, 8)) % 5000) + 5000,
            'salt' => substr($seed, 32, 16)
        ];
        return $algorithm;
    }
    
    // تشفير مزدوج باستخدام خوارزمية المستخدم
    public function encrypt($data) {
        // التشفير الأولي
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->userAlgorithm['primary']));
        $encrypted = openssl_encrypt(
            $data, 
            $this->userAlgorithm['primary'], 
            $this->masterKey, 
            0, 
            $iv
        );
        
        // التشفير الثانوي
        $iv2 = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->userAlgorithm['secondary']));
        $encrypted = openssl_encrypt(
            $encrypted . '::' . $iv, 
            $this->userAlgorithm['secondary'], 
            $this->masterKey, 
            0, 
            $iv2
        );
        
        return base64_encode($encrypted . '::' . $iv2);
    }
    
    // فك التشفير المزدوج
    public function decrypt($encryptedData) {
        $parts = explode('::', base64_decode($encryptedData));
        if(count($parts) != 2) return false;
        
        list($encryptedData, $iv2) = $parts;
        
        // فك التشفير الثانوي
        $decrypted = openssl_decrypt(
            $encryptedData, 
            $this->userAlgorithm['secondary'], 
            $this->masterKey, 
            0, 
            $iv2
        );
        
        $parts = explode('::', $decrypted);
        if(count($parts) != 2) return false;
        
        list($encryptedData, $iv) = $parts;
        
        // فك التشفير الأولي
        return openssl_decrypt(
            $encryptedData, 
            $this->userAlgorithm['primary'], 
            $this->masterKey, 
            0, 
            $iv
        );
    }
}
?>
