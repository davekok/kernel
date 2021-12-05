<?php

declare(strict_types=1);

namespace davekok\kernel\context;

class CryptoOptions
{
    public const WRAPPER = "ssl";
    public const INDEX_NAMES = [
        "peerName" => "peer_name",
        "verifyPeer" => "verify_peer",
        "verifyPeerName" => "verify_peer_name",
        "allowSelfSigned" => "allow_self_signed",
        "caFile" => "cafile",
        "caPath" => "capath",
        "localCertificate" => "local_cert",
        "localPrivateKey" => "local_pk",
        "passPhrase" => "passphrase",
        "verifyDepth" => "verify_depth",
        "ciphers" => "ciphers",
        "capturePeerCert" => "capture_peer_cert",
        "capturePeerCertChain" => "capture_peer_cert_chain",
        "SNIEnabled" => "SNI_enabled",
        "disableCompression" => "disable_compression",
        "peerFingerprint" => "peer_fingerprint",
        "securityLevel" => "security_level",
    ];

    public function __construct(
        /**
         * Peer name to be used. If this value is not set, then the name is guessed based on the hostname used when
         * opening the stream.
         */
        public string|null $peerName = null,

        /**
         * Require verification of SSL certificate used.
         */
        public bool|null $verifyPeer = null,

        /**
         * Require verification of peer name.
         */
        public bool|null $verifyPeerName = null,

        /**
         * Allow self-signed certificates. Requires verify_peer.
         */
        public bool|null $allowSelfSigned = null,

        /**
         * Location of Certificate Authority file on local filesystem which should be used with the verify_peer context
         * option to authenticate the identity of the remote peer.
         */
        public string|null $caFile = null,

        /**
         * If cafile is not specified or if the certificate is not found there, the directory pointed to by capath is
         * searched for a suitable certificate. capath must be a correctly hashed certificate directory.
         */
        public string|null $caPath = null,

        /**
         * Path to local certificate file on filesystem. It must be a PEM encoded file which contains your certificate
         * and private key. It can optionally contain the certificate chain of issuers. The private key also may be
         * contained in a separate file specified by local_pk.
         */
        public string|null $localCertificate = null,

        /**
         * Path to local private key file on filesystem in case of separate files for certificate (local_cert) and
         * private key.
         */
        public string|null $localPrivateKey = null,

        /**
         * Passphrase with which your local_cert file was encoded.
         */
        public string|null $passPhrase = null,

        /**
         * Abort if the certificate chain is too deep.
         */
        public int|null $verifyDepth = null,

        /**
         * Sets the list of available ciphers. The format of the string is described in
         * https://www.openssl.org/docs/manmaster/man1/ciphers.html#CIPHER-LIST-FORMAT.
         */
        public string|null $ciphers = null,

        /**
         * If set to true a peer_certificate context option will be created containing the peer certificate.
         */
        public bool|null $capturePeerCert = null,

        /**
         * If set to true a peer_certificate_chain context option will be created containing the certificate chain.
         */
        public bool|null $capturePeerCertChain = null,

        /**
         * If set to true server name indication will be enabled. Enabling SNI allows multiple certificates on the same IP address.
         */
        public bool|null $SNIEnabled = null,

        /**
         * If set, disable TLS compression. This can help mitigate the CRIME attack vector.
         */
        public bool|null $disableCompression = null,

        /**
         * Aborts when the remote certificate digest doesn't match the specified hash.
         * When a string is used, the length will determine which hashing algorithm is applied, either "md5" (32) or "sha1" (40).
         * When an array is used, the keys indicate the hashing algorithm name and each corresponding value is the expected digest.
         */
        public string|array|null $peerFingerprint = null,

        /**
         * Sets the security level. If not specified the library default security level is used. The security levels are described in
         * https://www.openssl.org/docs/man1.1.0/man3/SSL_CTX_get_security_level.html.
         */
        public int|null $securityLevel = null,
    ){}
}
