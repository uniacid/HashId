# Development Workflow

## Repository Structure

This project uses a dual-repository development workflow:

- **Public Fork**: `origin` → https://github.com/uniacid/HashId
  - Used for stable releases
  - Public-facing repository
  - Receives cherry-picked stable features

- **Private Development**: `private` → https://github.com/uniacid/HashId-Modernization-Private
  - Active development repository
  - All feature branches and experimentation
  - Private until ready for release

## Branch Strategy

- `master` - Stable production code (mirrors original)
- `dev/modernization-v4` - Main development branch for v4.0 modernization
- `feature/*` - Feature branches for specific modernization tasks

## Daily Development Workflow

### 1. Working on Features

```bash
# Start from development branch
git checkout dev/modernization-v4
git pull private dev/modernization-v4

# Create feature branch
git checkout -b feature/php83-attributes
git push private feature/php83-attributes --set-upstream

# Work and commit normally
git add .
git commit -m "Implement PHP 8.3 attributes"
git push private
```

### 2. Merging Features

```bash
# Switch to dev branch and merge
git checkout dev/modernization-v4
git merge feature/php83-attributes
git push private dev/modernization-v4

# Delete feature branch
git branch -d feature/php83-attributes
git push private --delete feature/php83-attributes
```

### 3. Release to Public

When ready to release stable version:

```bash
# Create release branch
git checkout -b release/v4.0.0
git push private release/v4.0.0

# Test thoroughly, then push to public fork
git push origin release/v4.0.0

# Create GitHub release on public repository
```

## Git Configuration

Current remotes:
```bash
origin   https://github.com/uniacid/HashId (fetch)
origin   https://github.com/uniacid/HashId (push)
private  https://github.com/uniacid/HashId-Modernization-Private.git (fetch)
private  https://github.com/uniacid/HashId-Modernization-Private.git (push)
```

Default push behavior:
```bash
git config push.default current
```

## Security Notes

- Keep sensitive testing configurations in `.env.local` (gitignored)
- Use GitHub Secrets for CI/CD in private repository
- Only push stable, tested code to public repository
- Document any breaking changes clearly before public release

## CI/CD

- **Private Repository**: Full CI/CD pipeline with all PHP/Symfony versions
- **Public Repository**: Release-focused CI/CD for stable versions

## Current Branch

You are now on: `dev/modernization-v4` → tracking `private/dev/modernization-v4`