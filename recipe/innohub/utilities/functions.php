<?php

namespace Deployer;

function getGitTagOrCommitHash(): \Closure
{
    return static function ($config = []): string {
        $commitHash = trim(runLocally('git log -n 1 --format="%h"'));
        $tag = trim(runLocally(sprintf('git tag --contains %s', $commitHash)));

        return $tag ?: $commitHash;
    };
}

function getGitCommitHash(): string
{
    $commitHash = trim(runLocally('git log -n 1 --format="%h"'));
    return $commitHash;
}

function getGitTagOrBranch(): string
{
    $commitHash = getGitCommitHash();
    $tag = trim(runLocally(sprintf('git tag --contains="%s"', $commitHash)));
    $branch = trim(runLocally(sprintf('(git branch --contains="%1$s" --merged || git branch --contains="%1$s") | grep "*"', $commitHash)));

    return $tag ?: $branch;
}
