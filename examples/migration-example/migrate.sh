#!/bin/bash

# HashId Bundle Migration Script (v3.x to v4.x)
# This script demonstrates how to migrate from HashId Bundle v3.x to v4.x

set -e

echo "=========================================="
echo "HashId Bundle Migration Script"
echo "Migrating from v3.x to v4.x"
echo "=========================================="
echo ""

# Check if vendor/bin/rector exists
if [ ! -f "../../vendor/bin/rector" ]; then
    echo "Error: Rector not found. Please install it first:"
    echo "composer require rector/rector --dev"
    exit 1
fi

# Step 1: Dry run to see what will change
echo "Step 1: Running Rector in dry-run mode to preview changes..."
echo "----------------------------------------"
../../vendor/bin/rector process v3-example --config=rector.php --dry-run

echo ""
echo "Step 2: Apply changes? (y/n)"
read -r response

if [[ "$response" == "y" || "$response" == "Y" ]]; then
    echo "Applying Rector changes..."
    ../../vendor/bin/rector process v3-example --config=rector.php
    echo "✓ Rector changes applied"
else
    echo "Skipping Rector changes"
fi

echo ""
echo "Step 3: Manual migration checklist"
echo "----------------------------------------"
echo "Please verify the following manual changes:"
echo ""
echo "[ ] 1. Update composer.json:"
echo "       - Change: \"pgs-soft/hashid-bundle\": \"^3.0\""
echo "       - To:     \"pgs-soft/hashid-bundle\": \"^4.0\""
echo ""
echo "[ ] 2. Update use statements:"
echo "       - From: use Pgs\\HashIdBundle\\Annotation\\Hash;"
echo "       - To:   use Pgs\\HashIdBundle\\Attribute\\Hash;"
echo ""
echo "[ ] 3. Convert annotations to attributes:"
echo "       - From: /**"
echo "               * @Hash(\"id\")"
echo "               */"
echo "       - To:   #[Hash('id')]"
echo ""
echo "[ ] 4. Update multiple parameters:"
echo "       - From: @Hash({\"param1\", \"param2\"})"
echo "       - To:   #[Hash(['param1', 'param2'])]"
echo ""
echo "[ ] 5. Add type hints to controller methods:"
echo "       - From: public function show(\$id)"
echo "       - To:   public function show(int \$id)"
echo ""
echo "[ ] 6. Use constructor property promotion (optional):"
echo "       - From: private \$service;"
echo "               public function __construct(Service \$service) {"
echo "                   \$this->service = \$service;"
echo "               }"
echo "       - To:   public function __construct("
echo "                   private readonly Service \$service"
echo "               ) {}"
echo ""
echo "[ ] 7. Test your application thoroughly"
echo ""

echo "Step 4: Comparing files"
echo "----------------------------------------"
echo "You can compare the migrated code with the v4 example:"
echo ""
echo "diff -u v3-example/OrderController.php v4-example/OrderController.php"
echo ""

echo "=========================================="
echo "Migration script completed!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Review the changes made by Rector"
echo "2. Complete the manual checklist above"
echo "3. Run your test suite"
echo "4. Test in development environment"
echo "5. Deploy to staging for validation"
echo ""
echo "For more information, see:"
echo "- UPGRADE-4.0.md in the root directory"
echo "- README.md in this directory"
echo "=========================================="