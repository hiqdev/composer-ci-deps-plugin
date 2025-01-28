<?php

namespace hiqdev\ComposerCiDeps\Service;

use Composer\IO\IOInterface;
use cweagans\Composer\Downloader\DownloaderBase;
use cweagans\Composer\Patch;
use Gitlab\Client;
use RuntimeException;

/**
 * Class GitLabPullRequestDownloader works with Patch objects, referencing GitLab merge requests.
 *
 * The downloader fetches the merge request from the GitLab API and creates a diff file.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class GitLabPullRequestDownloader extends DownloaderBase
{
    private Client $client;
    private string $token = "";

    public function download(Patch $patch): void
    {
        // Don't need to re-download a patch if it has already been downloaded.
        if (isset($patch->localPath) && !empty($patch->localPath)) {
            return;
        }

        if (!isset($patch->extra['gitlab'])) {
            return;
        }

        $this->client = $this->createGitLabClient();
        if (empty($patch->url)) {
            return;
        }

        try {
            $urlInfo = $this->parseGitLabUrl($patch->url);

            $this->client->setUrl('https://' . $urlInfo['host']);

            $mr = $this->client->mergeRequests()->show($urlInfo['project_path'], $urlInfo['mr_iid']);
            if (!$mr) {
                throw new RuntimeException("Could not find merge request #{$urlInfo['mr_iid']} in project {$urlInfo['project_path']}");
            }

            $sourceBranch = $mr['source_branch'];
            $sourceProjectId = $mr['source_project_id'];

            $project = $this->client->projects()->show($sourceProjectId);
            $httpUrlToRepo = $project['http_url_to_repo'];
            $authorizedHttpRepoUrl = str_replace('https://', "https://git:{$this->token}@", $httpUrlToRepo);

            $installedPackagePath = $this->getInstalledPackagePath($patch);
            $remoteName = 'mr_' . $urlInfo['mr_iid'];

            $this->io->write("      - Fetching PR {$urlInfo['mr_iid']} from {$urlInfo['project_path']}", true, IOInterface::VERBOSE);
            $prepareRemoteCommand = sprintf(
                'cd %s && (git remote rm %s 2>&1 || true) && git remote add %s %s 2>&1 && git fetch %s %s 2>&1',
                escapeshellarg($installedPackagePath),
                $remoteName,
                $remoteName,
                escapeshellarg($authorizedHttpRepoUrl),
                $remoteName,
                escapeshellarg($sourceBranch)
            );
            exec($prepareRemoteCommand, $output, $returnCode);
            if ($returnCode !== 0) {
                throw new RuntimeException("Failed to add remote: " . implode("\n", $output));
            }

            $this->io->write(
                "      - Building diff for PR {$urlInfo['mr_iid']} from {$urlInfo['project_path']}",
                true,
                IOInterface::VERBOSE
            );
            $diffCommand = sprintf(
                'cd %s && git diff ...%s/%s 2>&1',
                escapeshellarg($installedPackagePath),
                $remoteName,
                escapeshellarg($sourceBranch),
            );

            exec($diffCommand, $diffOutput, $diffReturnCode);
            if ($diffReturnCode !== 0) {
                throw new RuntimeException("Failed to create diff: " . implode("\n", $diffOutput));
            }

            $this->savePatch($patch, $diffOutput);

            $dropRemoteCommand = sprintf(
                'cd %s && git remote rm %s 2>&1',
                escapeshellarg($installedPackagePath),
                $remoteName
            );
            exec($dropRemoteCommand, $output, $returnCode);
            if ($returnCode !== 0) {
                throw new RuntimeException("Failed to drop remote: " . implode("\n", $output));
            }
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to process GitLab MR: " . $e->getMessage(), 0, $e);
        }
    }

    private function savePatch(Patch $patch, array $diffOutput): void
    {
        $patches_dir = sys_get_temp_dir() . '/composer-patches/';
        $filename = uniqid($patches_dir) . ".patch";
        if (!is_dir($patches_dir)) {
            mkdir($patches_dir);
        }

        file_put_contents($filename, implode("\n", $diffOutput) . "\n");
        $patch->localPath = $filename;
        $patch->sha256 = hash_file('sha256', $filename);
    }

    private function getInstalledPackagePath(Patch $patch): string
    {
        $package = $this->composer->getRepositoryManager()->getLocalRepository()->findPackage(
            $patch->package,
            '*'
        );

        if (!$package) {
            throw new RuntimeException("Could not find installed package {$patch->package}");
        }

        return $this->composer->getInstallationManager()->getInstallPath($package);
    }

    private function parseGitLabUrl(string $url): array
    {
        if (!preg_match('#^https?://([^/]+)/([^/]+/[^/]+)/-/merge_requests/(\d+)#', $url, $matches)) {
            throw new RuntimeException("Invalid GitLab merge request URL: {$url}");
        }

        return [
            'host' => $matches[1],
            'project_path' => $matches[2],
            'mr_iid' => (int)$matches[3],
        ];
    }

    private function createGitLabClient(): Client
    {
        $client = new Client();
        $this->token = getenv('GITLAB_REPO_ACCESS_TOKEN');
        if (empty($this->token)) {
            throw new RuntimeException('GITLAB_REPO_ACCESS_TOKEN environment variable is not set');
        }
        $client->authenticate($this->token, Client::AUTH_HTTP_TOKEN);

        return $client;
    }
}
