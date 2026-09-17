import { addAction } from '@wordpress/hooks';
import { registerModule } from '@divi/module-library';
import { createWidgetModule } from './module-factory';

import brandCatalogMetadata from './modules/brand-catalog/module.json';
import brandsListMetadata from './modules/brands-list/module.json';
import brandsProductsMetadata from './modules/brands-products/module.json';
import brandsDescriptionMetadata from './modules/brands-description/module.json';
import productBrandsInfoMetadata from './modules/product-brands-info/module.json';

const modules = [
  [brandCatalogMetadata, 'Brand Catalog preview is rendered on the frontend.'],
  [brandsListMetadata, 'Brands List preview is rendered on the frontend.'],
  [brandsProductsMetadata, 'Brands Products preview is rendered on the frontend.'],
  [brandsDescriptionMetadata, 'Product Brands Description preview is rendered on the frontend.'],
  [productBrandsInfoMetadata, 'Product Brand Info preview is rendered on the frontend.'],
];

addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'brbrand.divi5Modules', () => {
  modules.forEach(([metadata, placeholderLabel]) => {
    const module = createWidgetModule(metadata, placeholderLabel);
    const { metadata: moduleMetadata, ...moduleConfig } = module;
    registerModule(metadata, moduleConfig);
  });
});
