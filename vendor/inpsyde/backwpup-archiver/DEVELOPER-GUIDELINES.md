# Project Developers' Guidelines

## Setup for local development
In `wp-config.php` add `define('SCRIPT_DEBUG', true);`

```
$ composer install
$ vendor/bin/robo build:assets
```

## Semantic Versioning

We follow [Semantic Versioning](https://semver.org/).

## Git Flow

Documentation Link: https://www.atlassian.com/git/tutorials/comparing-workflows/gitflow-workflow

### Develop Branch

Don't delete the develop branch

### Feature Branch

Documentation Link: https://www.atlassian.com/git/tutorials/comparing-workflows/feature-branch-workflow

The reviewer will Approve the pull request and the developer will merge it into `develop` branch.
Every pull request must contains at least acceptance tests or unit tests.
Pull requests without tests must not be approved nor merged.

Create a feature branch

```bash
git checkout develop
git checkout -b feature/component/PROJECT_PREFIX-{ID}
```

Merge a feature branch

```bash
git checkout develop
git merge feature/component/PROJECT_PREFIX-{ID}
```

### Release Branch

A release should be tested by testers before getting merged into master.
In this case we'll create a package by the `release/{X.X.X}` branch and we'll name it `RC-{N}`
and we'll send the package to the testers along with the Jira issue that collect all of the issues we want to test.

If and only if all tests pass we'll merge the release.
The release have to be merged by the way after the product is released,
because during the tests period the package can have changes and the
marketing team want to have every RC package looks like a final release.
So the release branch contains all of the changes made for the release,
such as version number, changelog etc...

Create a release branch

```bash
git checkout develop
git checkout -b release/3.1.0
```

Merge a release branch

- Merge the release branch into master first
- Then merge the release into develop
- Then remove the release branch

```bash
git checkout master
git checkout merge release/3.1.0

git checkout develop
git merge release/3.1.0
```

Delete a release branch

```bash
git branch -d release/3.1.0
git push origin --delete release/3.1.0
```

### Hot fixes

Create an hotfix branch

```bash
git checkout master
git checkout -b hotfix/component/PROJECT_PREFIX-{ID}
```

Finish an hotfix

```bash
git checkout master
git merge hotfix/component/PROJECT_PREFIX-{ID}

git checkout develop
git merge hotfix/component/PROJECT_PREFIX-{ID}

git branch -d hotfix/component/PROJECT_PREFIX-{ID}
git push origin --delete hotfix/component/PROJECT_PREFIX-{ID}
```

### Commit Messages

Commit messages should be as descriptive as possible and maximum 50 characters long.

Because of this limitation **don't** use issue number in the commit message.

Every commit message should read well when starting with the sentence:

> *If merged this commit will...*

So, for example, a good message is:

> *Add new HTML class to the post translation metabox*

A bad message is:

> *Fixed post metabox stuff*

To refer to ticket system (JIRA..) please use an **additional line** of Git message.

E.g.:

```bash
git commit -m "Add new HTML class to the post translation metabox" -m "See PROJECT_PREFIX-123"
```

this way all 50 characters are used for a descriptive message, the commits history can be read without be polluted with things that has nothing to do with code, but the automatic connection with ticketing system is maintained.


#### .gitignore policy

In `.gitignore` we only keep files that are specific to the project. For files that should be ignored in all projects (e.g. IDE files like `.idea` or OS files like `.DS_Store`) every developer should set that in a global `.gitignore`.

To create a global `.gitignore` run in your console:

```bash
git config --global core.excludesfile ~/.gitignore_global
```

this will create  a file `.gitignore_global` in the home directory. Put there files that should be ignored everywhere... a good starting point could be:

```
.idea/
.DS_Store
.DS_Store?
._*
.Spotlight-V100
.Trashes
ehthumbs.db
Thumbs.db
.php_cs.cache
```

## Tests-Oriented Development

At the moment the test coverage of the plugin is sub-optimal. Things needs to change.

**Every new feature that is developed should have unit tests and if relevant browser/integration/e2e tests**.

It is not required to actually write tests _first_ (but if done, does not hurt) but the test files should be commited in the same commit of the production code change. So they need to be written before work for a new feature can be committed.

**When a bug is reported, the developer who is going to fix it needs to write one or more tests to reproduce the bug**. This need to be done **before** the code is changed.

After the test is written, the developer can fix the bug and will commit both test and code in same commit when the tests pass.
