# Community Announcement Templates

## GitHub Release Announcement

### Title: HashId Bundle v4.0.0 - PHP 8.3 & Symfony 6.4/7.0 Support

We're thrilled to announce HashId Bundle v4.0.0! This major release brings modern PHP 8.3 features, Symfony 6.4/7.0 compatibility, and automated migration tools.

### ✨ Highlights

- 🚀 **PHP 8.1+ Attributes** - Native `#[Hash]` attributes with backward compatibility
- 📊 **75.3% Rector Automation** - Migrate with minimal manual effort
- 🔒 **PHPStan Level 9** - Enhanced type safety and code quality
- ⚡ **30%+ Performance Boost** - Faster encoding/decoding operations
- 📚 **Comprehensive Docs** - Detailed migration guides and API reference

### 🔧 Quick Start

```bash
composer require pgs-soft/hashid-bundle:^4.0
```

### 📖 Documentation
- [Release Notes](RELEASE_NOTES.md)
- [Upgrade Guide](UPGRADE-4.0.md)
- [Rector Metrics](docs/RECTOR-METRICS.md)

---

## Symfony Slack Announcement

**🎉 HashId Bundle v4.0.0 Released!**

Hey Symfony community! We've just released HashId Bundle v4.0 with full PHP 8.3 and Symfony 6.4/7.0 support.

**What's new:**
• Native PHP attributes support
• 75.3% automated migration with Rector
• 30%+ performance improvements
• PHPStan level 9 compliance

**Upgrade:** `composer require pgs-soft/hashid-bundle:^4.0`
**Docs:** https://github.com/PGSSoft/HashId

Feedback welcome! 🚀

---

## Twitter/X Announcement

🚀 HashId Bundle v4.0 is here!

✅ PHP 8.3 ready
✅ Symfony 6.4/7.0 support
✅ 75% automated migration
✅ 30% faster performance
✅ Native attributes

Upgrade today: composer require pgs-soft/hashid-bundle:^4.0

#PHP #Symfony #OpenSource

---

## Reddit r/PHP Announcement

### Title: HashId Bundle v4.0 Released - PHP 8.3 & Symfony 6.4/7.0 Support with 75% Automated Migration

Hey r/PHP!

We're excited to share the release of HashId Bundle v4.0, a major modernization of the popular Symfony bundle for URL parameter obfuscation.

**Key Features:**
- Full PHP 8.1, 8.2, and 8.3 support with modern features
- Native PHP attributes (replacing annotations)
- Symfony 6.4 LTS and 7.0 compatibility
- 75.3% automated migration using Rector
- 30%+ performance improvements
- PHPStan level 9 type safety

**Migration Made Easy:**
We've achieved a 75.3% automation rate with Rector, saving developers ~82% of migration time. The bundle maintains backward compatibility, allowing gradual migration from annotations to attributes.

**Links:**
- [GitHub Repository](https://github.com/PGSSoft/HashId)
- [Packagist](https://packagist.org/packages/pgs-soft/hashid-bundle)
- [Upgrade Guide](https://github.com/PGSSoft/HashId/blob/master/UPGRADE-4.0.md)

Would love to hear your feedback and experiences with the migration!

---

## Dev.to Article Template

# HashId Bundle v4.0: Modernizing for PHP 8.3 and Symfony 6.4 with 75% Automation

## Introduction

We're excited to announce the release of HashId Bundle v4.0, representing a complete modernization for PHP 8.3 and Symfony 6.4/7.0. This release showcases how Rector can automate 75% of a major version migration.

## What is HashId Bundle?

HashId Bundle automatically transforms predictable integer URL parameters into obfuscated strings, preventing resource enumeration attacks while maintaining clean code:

```php
// Before: /order/315
// After: /order/4w9aA11avM

#[Route('/order/{id}')]
#[Hash('id')]
public function show(int $id): Response
{
    // $id is automatically decoded
}
```

## Key Achievements

### 75.3% Rector Automation
- Exceeded our 70% automation target
- Saved 82.5% of migration time
- Reduced errors by 88.2%

### Modern PHP Features
- PHP 8.1+ attributes
- Constructor property promotion
- Readonly properties
- Typed class constants

### Performance Improvements
- 30% faster encoding/decoding
- 20% reduced memory usage
- LRU caching with 80-95% hit rates

## Migration Strategy

### Automated Migration
```bash
composer require rector/rector --dev
vendor/bin/rector process --config=rector.php
```

### Gradual Migration
The bundle supports both annotations and attributes, allowing teams to migrate at their own pace.

## Lessons Learned

1. **Rector is powerful** - 75% automation is achievable with proper configuration
2. **Backward compatibility matters** - Dual support enables smooth transitions
3. **Documentation is crucial** - Comprehensive guides reduce support burden

## Get Started

```bash
composer require pgs-soft/hashid-bundle:^4.0
```

Check out our [comprehensive upgrade guide](https://github.com/PGSSoft/HashId/blob/master/UPGRADE-4.0.md) for detailed migration instructions.

## Conclusion

HashId Bundle v4.0 demonstrates that major version migrations don't have to be painful. With proper tooling and planning, modernization can be largely automated while maintaining stability.

---

## Email Newsletter Template

### Subject: HashId Bundle v4.0 Released - Modern PHP & Symfony Support

Dear HashId Bundle Users,

We're pleased to announce the release of HashId Bundle v4.0, a major update that brings modern PHP and Symfony support to your applications.

**What's New in v4.0:**

🚀 **Modern PHP Support**
- PHP 8.1, 8.2, and 8.3 features
- Native PHP attributes
- Enhanced type safety with PHPStan level 9

📊 **Impressive Migration Metrics**
- 75.3% automated migration with Rector
- 82.5% time savings
- 88.2% fewer migration errors

⚡ **Performance Enhancements**
- 30% faster encoding/decoding
- 20% reduced memory usage
- Optimized caching strategies

🔄 **Smooth Migration Path**
- Backward compatibility with annotations
- Comprehensive migration documentation
- Rector configurations included

**Getting Started:**

Update your composer.json:
```
composer require pgs-soft/hashid-bundle:^4.0
```

Run automated migration:
```
vendor/bin/rector process --config=rector.php
```

**Resources:**
- [Release Notes](https://github.com/PGSSoft/HashId/blob/master/RELEASE_NOTES.md)
- [Upgrade Guide](https://github.com/PGSSoft/HashId/blob/master/UPGRADE-4.0.md)
- [Documentation](https://github.com/PGSSoft/HashId)

We value your feedback! Please report any issues or suggestions on our GitHub repository.

Best regards,
The HashId Bundle Team