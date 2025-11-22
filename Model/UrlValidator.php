<?php
declare(strict_types=1);

namespace Magendoo\ProductWebhook\Model;

use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Validates webhook URLs to prevent SSRF attacks
 */
class UrlValidator
{
    /**
     * Blacklisted schemes
     */
    private const BLACKLISTED_SCHEMES = ['file', 'ftp', 'gopher', 'dict', 'php'];

    /**
     * Blacklisted hosts/patterns
     */
    private const BLACKLISTED_HOSTS = [
        'localhost',
        '127.0.0.1',
        '0.0.0.0',
        '169.254.169.254', // AWS metadata
        '::1',
    ];

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Validate webhook URL
     *
     * @param string $url
     * @return bool
     * @throws LocalizedException
     */
    public function validate(string $url): bool
    {
        if (empty($url)) {
            throw new LocalizedException(__('Webhook URL cannot be empty'));
        }

        // Parse URL
        $parsedUrl = parse_url($url);
        if ($parsedUrl === false || !isset($parsedUrl['scheme'], $parsedUrl['host'])) {
            $this->logger->error('Invalid webhook URL format', ['url' => $url]);
            throw new LocalizedException(__('Invalid URL format. Must include scheme (https://) and host.'));
        }

        // Validate scheme - only allow HTTPS
        if (!in_array(strtolower($parsedUrl['scheme']), ['https'])) {
            $this->logger->error('Invalid webhook URL scheme', [
                'url' => $url,
                'scheme' => $parsedUrl['scheme']
            ]);
            throw new LocalizedException(__('Only HTTPS URLs are allowed for security reasons.'));
        }

        // Check blacklisted schemes
        if (in_array(strtolower($parsedUrl['scheme']), self::BLACKLISTED_SCHEMES)) {
            $this->logger->critical('Blacklisted scheme detected in webhook URL', [
                'url' => $url,
                'scheme' => $parsedUrl['scheme']
            ]);
            throw new LocalizedException(__('This URL scheme is not allowed.'));
        }

        // Validate host is not blacklisted
        $host = strtolower($parsedUrl['host']);
        if (in_array($host, self::BLACKLISTED_HOSTS)) {
            $this->logger->critical('Blacklisted host detected in webhook URL', [
                'url' => $url,
                'host' => $host
            ]);
            throw new LocalizedException(__('This host is not allowed (internal/localhost addresses blocked).'));
        }

        // Block private IP ranges
        if ($this->isPrivateIp($host)) {
            $this->logger->critical('Private IP address detected in webhook URL', [
                'url' => $url,
                'host' => $host
            ]);
            throw new LocalizedException(__('Private IP addresses are not allowed.'));
        }

        return true;
    }

    /**
     * Check if host is a private IP address
     *
     * @param string $host
     * @return bool
     */
    private function isPrivateIp(string $host): bool
    {
        // Resolve hostname to IP if needed
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);

        // If resolution failed, treat as potentially dangerous
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            return false; // Not an IP, let other validation handle it
        }

        // Check if IP is in private range
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
