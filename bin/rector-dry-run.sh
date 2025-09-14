#!/bin/bash

# HashId Bundle - Rector Dry-Run Analysis Script
# 
# This script provides a safe way to analyze potential Rector transformations
# without modifying any files. Use this for previewing changes before applying them.

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default configuration
CONFIG_FILE=""
OUTPUT_FORMAT="console"
PATHS=""

show_help() {
    echo "HashId Bundle - Rector Dry-Run Analysis"
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -c, --config FILE      Rector configuration file to use"
    echo "                         (default: rector.php)"
    echo "  -f, --format FORMAT    Output format: console, json"  
    echo "                         (default: console)"
    echo "  -p, --path PATH        Specific path to analyze"
    echo "                         (default: src and tests directories)"
    echo "  -h, --help            Show this help message"
    echo ""
    echo "Available configurations:"
    echo "  rector.php             Main configuration (imports others selectively)"
    echo "  rector-php81.php       PHP 8.1 features only"
    echo "  rector-symfony.php     Symfony 6.4 compatibility only" 
    echo "  rector-quality.php     Code quality improvements only"
    echo "  rector-php82.php       PHP 8.2 features (placeholder)"
    echo "  rector-php83.php       PHP 8.3 features (placeholder)"
    echo ""
    echo "Examples:"
    echo "  $0                                    # Analyze with main config"
    echo "  $0 -c rector-php81.php               # Analyze PHP 8.1 features only"
    echo "  $0 -c rector-quality.php -f json     # Quality analysis with JSON output"
    echo "  $0 -p src/Service                    # Analyze specific directory"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -c|--config)
            CONFIG_FILE="$2"
            shift 2
            ;;
        -f|--format)
            OUTPUT_FORMAT="$2"
            shift 2
            ;;
        -p|--path)
            PATHS="$2"
            shift 2
            ;;
        -h|--help)
            show_help
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            show_help
            exit 1
            ;;
    esac
done

# Set default config if not specified
if [[ -z "$CONFIG_FILE" ]]; then
    CONFIG_FILE="rector.php"
fi

# Check if config file exists
if [[ ! -f "$CONFIG_FILE" ]]; then
    echo -e "${RED}Error: Configuration file '$CONFIG_FILE' not found${NC}"
    exit 1
fi

# Check if vendor/bin/rector exists
if [[ ! -f "vendor/bin/rector" ]]; then
    echo -e "${RED}Error: Rector not installed. Run 'composer install' first.${NC}"
    echo -e "${YELLOW}Note: Rector installation may require dependency updates.${NC}"
    exit 1
fi

echo -e "${BLUE}HashId Bundle - Rector Dry-Run Analysis${NC}"
echo -e "${BLUE}======================================${NC}"
echo ""
echo -e "${GREEN}Configuration:${NC} $CONFIG_FILE"
echo -e "${GREEN}Output Format:${NC} $OUTPUT_FORMAT"

if [[ -n "$PATHS" ]]; then
    echo -e "${GREEN}Analyzing Path:${NC} $PATHS"
fi

echo ""
echo -e "${YELLOW}Running dry-run analysis (no files will be modified)...${NC}"
echo ""

# Build rector command
RECTOR_CMD="vendor/bin/rector process --dry-run --config=$CONFIG_FILE"

if [[ "$OUTPUT_FORMAT" == "json" ]]; then
    RECTOR_CMD="$RECTOR_CMD --output-format=json"
fi

if [[ -n "$PATHS" ]]; then
    RECTOR_CMD="$RECTOR_CMD $PATHS"
fi

# Execute the command
echo -e "${BLUE}Command:${NC} $RECTOR_CMD"
echo ""

if eval "$RECTOR_CMD"; then
    echo ""
    echo -e "${GREEN}✓ Dry-run completed successfully${NC}"
    echo ""
    echo -e "${YELLOW}Next steps:${NC}"
    echo "1. Review the proposed changes above"
    echo "2. If satisfied, run without --dry-run to apply changes"
    echo "3. Run tests after applying changes"
    echo ""
    echo -e "${BLUE}Example:${NC} vendor/bin/rector process --config=$CONFIG_FILE"
else
    echo ""
    echo -e "${RED}✗ Dry-run encountered issues${NC}"
    echo ""
    echo -e "${YELLOW}Common solutions:${NC}"
    echo "1. Check that all dependencies are installed"
    echo "2. Verify configuration file syntax"
    echo "3. Review any error messages above"
    exit 1
fi