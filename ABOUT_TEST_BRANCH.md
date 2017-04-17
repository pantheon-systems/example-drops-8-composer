# About the Test Branch

The test-2.x branch is used to test the 2.x branch of the [Terminus Build Tools Plugin](https://github.com/pantheon-systems/terminus-build-tools-plugin).  This is a fork of the `test` branch with modifications for the newer version of the Build Tools plugin. In time, these modifications will be merged into the master branch.

The only difference between the `test` branch and the `master` branch is shown below:
```
machine:
  environment:
    BUILD_TOOLS_VERSION: '^1'

dependencies:
  override:
    - composer create-project -n -d ~/.terminus/plugins pantheon-systems/terminus-build-tools-plugin:$BUILD_TOOLS_VERSION --stability=dev
```
These changes exist so that the Terminus Build Tools Plugin tests can override the `BUILD_TOOLS_VERSION` in the CircleCI environments section of the created project to ensure that the System-Under-Test remains consistent between the derived project and the project being tested.

Every now and then, the `test` branch should be rebased with `master`.
