# Configuration Reference

Complete configuration reference for HashId Bundle v4.

## Full Configuration Example

```yaml
# config/packages/pgs_hash_id.yaml
pgs_hash_id:
    # Legacy converter configuration (backward compatibility)
    converter:
        hashids:
            salt: 'legacy-salt'
            min_hash_length: 10
            alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
    
    # Modern hashers configuration (recommended)
    hashers:
        # Each key is a hasher name that can be referenced in controllers
        default:
            salt: '%env(HASHID_SALT)%'
            min_hash_length: 10
            alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
            enabled: true
        
        secure:
            salt: '%env(HASHID_SECURE_SALT)%'
            min_hash_length: '%env(int:HASHID_SECURE_MIN_LENGTH)%'
            alphabet: '%env(HASHID_SECURE_ALPHABET)%'
            enabled: true
        
        # Additional hashers as needed...
```

## Configuration Options

### Root Level

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `converter` | array | - | Legacy configuration for backward compatibility |
| `hashers` | array | - | Modern multiple hasher configurations |

### Converter Options (Legacy)

Located under `pgs_hash_id.converter.hashids`:

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `salt` | string | `''` | Salt for hash generation |
| `min_hash_length` | int | `10` | Minimum length of generated hashes |
| `alphabet` | string | `[a-zA-Z0-9]` | Characters used in hash generation |

### Hasher Options (Modern)

Each hasher under `pgs_hash_id.hashers.[name]`:

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `salt` | string | `''` | Salt for hash generation. Supports env vars |
| `min_hash_length` | int/string | `10` | Minimum hash length. Supports typed env vars |
| `alphabet` | string | `[a-zA-Z0-9]` | Character set for hashes. Supports env vars |
| `enabled` | bool | `true` | Whether this hasher is active |

## Environment Variables

### Basic Usage

```yaml
pgs_hash_id:
    hashers:
        default:
            salt: '%env(HASHID_SALT)%'
```

### Typed Environment Variables

```yaml
pgs_hash_id:
    hashers:
        default:
            # String (explicit)
            salt: '%env(string:HASHID_SALT)%'
            
            # Integer
            min_hash_length: '%env(int:HASHID_MIN_LENGTH)%'
            
            # With default value
            alphabet: '%env(default:abc123:HASHID_ALPHABET)%'
```

### Advanced Environment Variables

```yaml
pgs_hash_id:
    hashers:
        default:
            # Read from file
            salt: '%env(file:HASHID_SALT_FILE)%'
            
            # Base64 decode
            salt: '%env(base64:HASHID_SALT_B64)%'
            
            # JSON decode
            alphabet: '%env(json:HASHID_CONFIG)%'
            
            # Nested processors
            min_hash_length: '%env(int:default:10:HASHID_LENGTH)%'
```

## Validation Rules

### Salt Validation
- Cannot be empty in production (warning issued)
- Any string value is accepted
- Environment variables are not validated until resolved

### Min Hash Length Validation
- Must be non-negative integer
- Maximum value: 255
- Default: 10

### Alphabet Validation
- Minimum 4 unique characters (Hashids requirement)
- Maximum 256 characters
- Must contain only unique characters
- Default: alphanumeric (62 characters)

### Hasher Name Validation
- Alphanumeric, hyphens, dots, and underscores allowed
- Case-sensitive
- Reserved names: none

## Configuration by Use Case

### Basic Setup

```yaml
pgs_hash_id:
    hashers:
        default:
            salt: '%env(HASHID_SALT)%'
            min_hash_length: 10
```

### Multi-Environment Setup

```yaml
# config/packages/pgs_hash_id.yaml
pgs_hash_id:
    hashers:
        default:
            salt: '%env(HASHID_SALT)%'
            min_hash_length: '%env(int:HASHID_MIN_LENGTH)%'
            alphabet: '%env(default:abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890:HASHID_ALPHABET)%'
```

```bash
# .env (defaults)
HASHID_MIN_LENGTH=10

# .env.local (development)
HASHID_SALT=dev-salt-not-secret

# .env.prod (production - use secret management)
HASHID_SALT=secret-production-salt
HASHID_MIN_LENGTH=15
```

### Security-Focused Setup

```yaml
pgs_hash_id:
    hashers:
        # Public content - shorter hashes OK
        public:
            salt: '%env(HASHID_PUBLIC_SALT)%'
            min_hash_length: 5
            alphabet: 'abcdefghijklmnopqrstuvwxyz1234567890'
        
        # Default - balanced security
        default:
            salt: '%env(HASHID_SALT)%'
            min_hash_length: 12
            alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
        
        # Sensitive data - maximum security
        secure:
            salt: '%env(HASHID_SECURE_SALT)%'
            min_hash_length: 25
            alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*()-_=+'
        
        # Admin only - extra long
        admin:
            salt: '%env(HASHID_ADMIN_SALT)%'
            min_hash_length: 30
            alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*'
```

### API-Oriented Setup

```yaml
pgs_hash_id:
    hashers:
        # API v1 - maintain backward compatibility
        api_v1:
            salt: '%env(HASHID_API_V1_SALT)%'
            min_hash_length: 10
            alphabet: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
        
        # API v2 - enhanced security
        api_v2:
            salt: '%env(HASHID_API_V2_SALT)%'
            min_hash_length: 15
            alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
        
        # Webhooks - consistent format
        webhook:
            salt: '%env(HASHID_WEBHOOK_SALT)%'
            min_hash_length: 20
            alphabet: '0123456789abcdef'  # Hex-like appearance
```

## Performance Considerations

### Caching
- Hasher instances are cached after first use
- Configuration is processed once during container compilation
- No runtime configuration parsing overhead

### Alphabet Size Impact
- Larger alphabets = shorter hashes for same entropy
- Smaller alphabets = longer hashes needed
- Recommended: 62 characters (alphanumeric)

### Min Hash Length Impact
- Longer hashes = more computation
- Recommended: 10-15 for general use
- Use 20+ for sensitive resources

## Migration from v3

### Option 1: Minimal Changes
Keep existing configuration:
```yaml
pgs_hash_id:
    converter:
        hashids:
            salt: 'your-existing-salt'
            min_hash_length: 10
```

### Option 2: Modern with Backward Compatibility
```yaml
pgs_hash_id:
    # Keep for backward compatibility
    converter:
        hashids:
            salt: '%env(HASHID_SALT)%'
            min_hash_length: 10
    
    # New configuration
    hashers:
        default:
            salt: '%env(HASHID_SALT)%'
            min_hash_length: 10
```

### Option 3: Full Migration
```yaml
pgs_hash_id:
    hashers:
        default:
            salt: '%env(HASHID_SALT)%'
            min_hash_length: 10
            alphabet: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
```

## Debugging Configuration

### View Processed Configuration
```bash
php bin/console debug:config pgs_hash_id
```

### Check Environment Variables
```bash
php bin/console debug:container --env-vars | grep HASHID
```

### List All Parameters
```bash
php bin/console debug:container --parameters | grep pgs_hash_id
```

### Test Configuration
```bash
php bin/console config:dump-reference pgs_hash_id
```