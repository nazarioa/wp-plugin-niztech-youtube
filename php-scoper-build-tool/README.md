## PHP Scoping for Niztech Youtube

Why do we have a build directory with composer.json? That is a good question!

This came into existence because Niztech YouTube has a dependency on YouTube API PHP SDK.
That SDK has itself some depndencies. Those depdnedecies conflict with other wordpress plugins / themes.

In this case, the version of Mongologger that the SDK uses conflicts with something else... I could not figure out what.

The solution is scope this plugin's dependencies.
