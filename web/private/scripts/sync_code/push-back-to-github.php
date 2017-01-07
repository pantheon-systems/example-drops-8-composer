<?php

// This is only for multidev environments.
if (in_array($_ENV['PANTHEON_ENVIRONMENT'], ['dev', 'test', 'live'])) {
  return;
}

/**
 * This script will attempt to push "lean" changes back upstream.
 */
$bindingDir = $_SERVER['HOME'];
$repositoryRoot = "$bindingDir/code";
$docRoot = "$repositoryRoot/" . $_SERVER['DOCROOT'];

print "Enter push-back-to-github. repository root is $repositoryRoot, docRoot is $docRoot\n";

$buildMetadataFile = "$repositoryRoot/build-metadata.json";
if (!file_exists($buildMetadataFile)) {
  print "Could not find build metadata file, $buildMetadataFile\n";
  return;
}
$buildMetadataFileContents = file_get_contents($buildMetadataFile);
$buildMetadata = json_decode($buildMetadataFileContents, true);
if (empty($buildMetadata)) {
  print "No data in build metadata\n";
  return;
}

print "::::::::::::::::: Build Metadata :::::::::::::::::\n";
var_export($buildMetadata);
print "\n\n";

$privateFiles = "$bindingDir/files/private";
$gitHubSecretsFile = "$privateFiles/github-secrets.json";
if (!file_exists($privateFiles)) {
  print "Could not find $gitHubSecretsFile\n";
  return;
}
$gitHubSecretsContents = file_get_contents($gitHubSecretsFile);
$gitHubSecrets = json_decode($gitHubSecretsContents, true);
if (empty($gitHubSecrets)) {
  print "No data in GitHub secrets\n";
  return;
}

print "::::::::::::::::: GitHub Secrets :::::::::::::::::\n";
var_export($gitHubSecrets);
print "\n\n";

// The remote repo to push to
$upstreamRepo = $buildMetadata['url'];
if (!empty($gitHubSecrets) && array_key_exists('token', $gitHubSecrets)) {
  $token = $gitHubSecrets['token'];
  $upstreamRepo = str_replace('git@github.com:', 'https://github.com/', $upstreamRepo);
  $upstreamRepo = str_replace('https://', "https://$token:x-oauth-basic@", $upstreamRepo);
}

// The last commit made on the lean repo prior to creating the build artifacts
$fromSha = $buildMetadata['sha'];

// The name of the PR branch
$branch = $buildMetadata['ref'];

// The name of our new branch
$prBranch = "new-$branch";

// The commit to cherry-pick
$currentCommit = exec('git rev-parse HEAD');

// TODO: vet the contents of the commit for applicability first.
// If the commit is 'mixed', it must be rejected. Is there any
// way to recover from this? Maybe not.

print "::::::::::::::::: Info :::::::::::::::::\n";
print "We are going to check out $prBranch from $fromSha, then cherry-pick $currentCommit and push it back to $upstreamRepo\n";

// Create our new branch without switching to it. We start with
// '$fromSha' to avoid placing any build artifacts on our branch.
passthru("git -C $repositoryRoot branch -f $prBranch $fromSha");

$pantheonRepository = "file://$repositoryRoot";
$workRepository = "$bindingDir/tmp/scratchRepository";

// Clone our current repository -- but only take the current branch
passthru("git clone $pantheonRepository --branch $prBranch --single-branch $workRepository");

// Use show | apply to do the equivalent of a cherry-pick
// between the two repositories.
passthru("git -C $repositoryRoot show $currentCommit | git -c $workRepository apply -Xthiers");

// Push the new branch back to Pantheon
passthru("git -C $workRepository push $upstreamRepo $prBranch");

// We don't need the pr branch or the second working repository any longer
passthru("git -C $repositoryRoot branch -D $prBranch");
passthru("rm -rf $workRepository");

