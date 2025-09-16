# HashId Bundle v4.0 Beta Testing Program - Join Us!

## Calling All Symfony Developers!

We're excited to announce the **HashId Bundle v4.0 Beta Testing Program**! After months of development, we're ready to share our modernized bundle that brings PHP 8.3 features and automated migration tools to the Symfony community.

## What's New in v4.0?

### Major Improvements
- **PHP 8.3 Support**: Full compatibility with the latest PHP features
- **Symfony 6.4/7.0 Ready**: Optimized for the latest Symfony versions
- **70%+ Automated Migration**: Rector-powered upgrade from v3.x
- **30%+ Performance Boost**: Faster encoding/decoding operations
- **Modern PHP Features**: Attributes, readonly properties, typed constants

### Key Innovation: Rector Automation
Our biggest achievement is the **automated migration workflow** that converts your v3.x codebase to v4.0 with minimal manual intervention:

```bash
# One command migration
vendor/bin/rector process --config=vendor/pgs/hashid-bundle/rector-configs/beta/rector-php81-beta.php
```

## Why Join the Beta Program?

### For You
- **Early Access**: Be among the first to use v4.0 features
- **Influence Development**: Your feedback shapes the final release
- **Migration Support**: Direct help from the development team
- **Recognition**: Beta testers credited in release notes

### For the Community
- **Validate Automation**: Help us achieve 70%+ automated migration
- **Improve Quality**: Find and fix issues before public release
- **Share Experience**: Your use cases help others migrate smoothly
- **Shape the Future**: Influence the direction of the bundle

## Who Should Participate?

### Ideal Beta Testers
- Projects currently using HashId v3.x
- Teams planning PHP 8.x upgrades
- Applications with diverse routing patterns
- Developers interested in Rector automation

### We Especially Need
- Large-scale applications (100+ controllers)
- Complex routing configurations
- Custom HashId extensions
- Multi-language applications

## How to Join

### Quick Start (5 minutes)
```bash
# 1. Create a test branch
git checkout -b hashid-v4-beta

# 2. Install beta version
composer require pgs/hashid-bundle:"4.0.0-beta1" --dev

# 3. Run automated migration
vendor/bin/rector process --config=vendor/pgs/hashid-bundle/rector-configs/beta/rector-php81-beta.php

# 4. Test and provide feedback
./vendor/bin/phpunit
```

### Full Testing (30 minutes)
1. Follow our [Beta Testing Guide](https://github.com/pgs-soft/hashid-bundle/blob/4.0/docs/beta/BETA_TESTING_GUIDE.md)
2. Use our [Migration Checklist](https://github.com/pgs-soft/hashid-bundle/blob/4.0/docs/beta/RECTOR_MIGRATION_CHECKLIST.md)
3. Report issues via [GitHub Templates](https://github.com/pgs-soft/hashid-bundle/issues/new/choose)
4. Complete our [Feedback Survey](https://forms.gle/hashid-v4-beta)

## Beta Testing Timeline

### Phase 1: Early Access (Weeks 1-2)
- Limited release to selected testers
- Focus on Rector automation validation
- Daily support and rapid fixes

### Phase 2: Open Beta (Weeks 3-4)
- Public beta availability
- Community-wide testing
- Performance benchmarking

### Phase 3: Release Candidate (Week 5)
- Final fixes and optimizations
- Documentation completion
- Production readiness verification

### Target Release: Week 6
- v4.0.0 stable release
- Full documentation
- Migration tools and guides

## Support During Beta

### Get Help
- **Discord Channel**: [#hashid-beta](https://discord.gg/symfony-hashid)
- **GitHub Discussions**: [Beta Forum](https://github.com/pgs-soft/hashid-bundle/discussions/categories/beta)
- **Email Support**: hashid-beta@pgs-soft.com
- **Office Hours**: Tuesdays & Thursdays, 2-4 PM UTC

### Report Issues
- [Rector Automation Issues](https://github.com/pgs-soft/hashid-bundle/issues/new?template=rector-automation-report.yml)
- [Migration Problems](https://github.com/pgs-soft/hashid-bundle/issues/new?template=migration-issue.yml)
- [General Feedback](https://github.com/pgs-soft/hashid-bundle/issues/new?template=beta-feedback.yml)

## Success Metrics We're Tracking

Help us achieve these goals:

- **70%+ Automation**: Rector handles most migration work
- **90%+ Success Rate**: Custom rules work effectively
- **50%+ Time Savings**: Faster than manual migration
- **Zero Breaking Changes**: Full backward compatibility
- **30%+ Performance Gain**: Improved encoding/decoding

## Docker Test Environment

Test in isolated environments:

```bash
# Clone beta testing kit
git clone https://github.com/pgs-soft/hashid-bundle-beta-test
cd hashid-bundle-beta-test

# Test on multiple PHP versions
docker-compose up -d
docker-compose exec php81 /scripts/run-migration.sh
docker-compose exec php82 /scripts/run-migration.sh
docker-compose exec php83 /scripts/run-migration.sh
```

## Rewards & Recognition

### Beta Testers Receive
- Early access to v4.0 features
- Direct influence on development
- Recognition in release notes
- HashId Beta Tester badge (GitHub)
- Priority support for migration

### Top Contributors
- Featured case studies
- Co-authorship opportunities
- Conference talk mentions
- Special thanks in documentation

## Join Us Today!

Your participation makes HashId v4.0 better for everyone. Whether you can spare 5 minutes for a quick test or dive deep into comprehensive testing, your feedback is invaluable.

### Ready to Start?

1. **Star the repository**: [github.com/pgs-soft/hashid-bundle](https://github.com/pgs-soft/hashid-bundle)
2. **Join Discord**: [discord.gg/symfony-hashid](https://discord.gg/symfony-hashid)
3. **Install beta**: `composer require pgs/hashid-bundle:"4.0.0-beta1"`
4. **Run migration**: `vendor/bin/rector process`
5. **Share feedback**: [Beta Survey](https://forms.gle/hashid-v4-beta)

## Questions?

- **Technical**: Post in [GitHub Discussions](https://github.com/pgs-soft/hashid-bundle/discussions)
- **General**: Email hashid-beta@pgs-soft.com
- **Urgent**: Discord [#hashid-beta](https://discord.gg/symfony-hashid)

---

**Thank you for helping make HashId v4.0 production-ready!**

The HashId Team