import React, { useEffect, useMemo, useRef, useState } from 'react';

const {
  ModuleContainer,
  StyleContainer,
  elementClassnames,
} = window?.divi?.module || {};

const ModuleStyles = ({
  elements,
  settings,
  mode,
  state,
  noStyleTag,
}) => (
  <StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
    {elements.style({
      attrName: 'module',
      styleProps: {
        disabledOn: {
          disabledModuleVisibility: settings?.disabledModuleVisibility,
        },
      },
    })}
  </StyleContainer>
);

const ModuleScriptData = ({ elements }) => (
  <React.Fragment>
    {elements.scriptData({
      attrName: 'module',
    })}
  </React.Fragment>
);

const moduleClassnames = ({ classnamesInstance, attrs }) => {
  classnamesInstance.add(
    elementClassnames({
      attrs: attrs?.module?.decoration ?? {},
    }),
  );
};

const option = (label) => ({ label });

const selectOptions = {
  unit: {
    px: option('px'),
    '%': option('%'),
  },
  imageFit: {
    cover: option('Cover'),
    contain: option('Contain'),
    fill: option('Fill'),
    none: option('None'),
  },
  brandImageAlign: {
    above: option('Above name'),
    left: option('Left to name'),
    right: option('Right to name'),
    under: option('Under name'),
  },
  thumbnailAlign: {
    left: option('Left to text'),
    right: option('Right to text'),
    none: option('None'),
  },
  bannerAlign: {
    left: option('Left'),
    right: option('Right'),
    center: option('Center'),
  },
  brandOrderby: {
    alphabet: option('Alphabet'),
    products: option('Number of products'),
    order: option('Order'),
    random: option('Random'),
  },
  brandOrder: {
    ASC: option('Asc'),
    DESC: option('Desc'),
  },
  hierarchy: {
    top: option('Only top level'),
    children: option('Only children (without hierarchy)'),
    expand: option('Show full hierarchy'),
    by_click: option('Expand by click'),
    all: option('All brands without hierarchy'),
  },
  groupby: {
    alphabet: option('Alphabet'),
    category: option('Category'),
    none: option('None'),
  },
  catalogStyle: {
    vertical: option('Vertical'),
    horizontal: option('Horizontal'),
  },
  productOrderby: {
    title: option('Product title'),
    name: option('Product name'),
    date: option('Date of creation'),
    modified: option('Last modified date'),
    rand: option('Random'),
  },
  productOrder: {
    asc: option('Asc'),
    desc: option('Desc'),
  },
  brandField: {
    name: option('Name'),
    slug: option('Slug'),
    term_id: option('ID'),
  },
};

const fieldTypeMap = {
  img_width_units: { name: 'divi/select', props: { options: selectOptions.unit } },
  img_height_units: { name: 'divi/select', props: { options: selectOptions.unit } },
  thumbnail_width_units: { name: 'divi/select', props: { options: selectOptions.unit } },
  thumbnail_height_units: { name: 'divi/select', props: { options: selectOptions.unit } },
  banner_width_units: { name: 'divi/select', props: { options: selectOptions.unit } },
  banner_height_units: { name: 'divi/select', props: { options: selectOptions.unit } },
  img_fit: { name: 'divi/select', props: { options: selectOptions.imageFit } },
  thumbnail_fit: { name: 'divi/select', props: { options: selectOptions.imageFit } },
  banner_fit: { name: 'divi/select', props: { options: selectOptions.imageFit } },
  img_align: { name: 'divi/select', props: { options: selectOptions.brandImageAlign } },
  thumbnail_align: { name: 'divi/select', props: { options: selectOptions.thumbnailAlign } },
  banner_align: { name: 'divi/select', props: { options: selectOptions.bannerAlign } },
  hierarchy: { name: 'divi/select', props: { options: selectOptions.hierarchy } },
  groupby: { name: 'divi/select', props: { options: selectOptions.groupby } },
  style: { name: 'divi/select', props: { options: selectOptions.catalogStyle } },
  brand_field: { name: 'divi/select', props: { options: selectOptions.brandField } },
  border_color: { name: 'divi/color-picker' },
};

const brandOrderingModules = new Set([
  'brbrand/brand-catalog',
  'brbrand/brands-list',
]);

const productOrderingModules = new Set([
  'brbrand/brands-products',
  'brbrand/brands-description',
  'brbrand/product-brands-info',
]);

const getFieldDefinition = (metadata, attrName) => {
  if ('orderby' === attrName) {
    if (brandOrderingModules.has(metadata.name)) {
      return { name: 'divi/select', props: { options: selectOptions.brandOrderby } };
    }

    if (productOrderingModules.has(metadata.name)) {
      return { name: 'divi/select', props: { options: selectOptions.productOrderby } };
    }
  }

  if ('order' === attrName) {
    if (brandOrderingModules.has(metadata.name)) {
      return { name: 'divi/select', props: { options: selectOptions.brandOrder } };
    }

    if (productOrderingModules.has(metadata.name)) {
      return { name: 'divi/select', props: { options: selectOptions.productOrder } };
    }
  }

  return fieldTypeMap[attrName];
};

const normalizeMetadataFields = (metadata) => {
  Object.entries(metadata?.attributes ?? {}).forEach(([attrName, attr]) => {
    const item = attr?.settings?.innerContent?.item;
    const fieldDefinition = getFieldDefinition(metadata, attrName);

    if (!item?.component || !fieldDefinition) {
      return;
    }

    item.component = {
      ...item.component,
      name: fieldDefinition.name,
      props: {
        ...(item.component.props ?? {}),
        ...(fieldDefinition.props ?? {}),
      },
    };
  });

  return metadata;
};

const getPreviewConfig = () => {
  if (window?.BrBrandDivi5Preview) {
    return window.BrBrandDivi5Preview;
  }

  const scriptSrc = document?.currentScript?.src;
  if (!scriptSrc) {
    return {};
  }

  const params = new URL(scriptSrc).searchParams;

  return {
    ajaxUrl: decodeURIComponent(params.get('brbrand_ajax_url') || ''),
    action: params.get('brbrand_action') || '',
    nonce: params.get('brbrand_nonce') || '',
  };
};

const previewConfig = getPreviewConfig();

const WidgetPreview = ({ attrs, metadata, placeholderLabel }) => {
  const previewRef = useRef(null);
  const [state, setState] = useState({
    html: '',
    isLoading: true,
    error: '',
  });
  const attrsKey = useMemo(() => JSON.stringify(attrs ?? {}), [attrs]);

  useEffect(() => {
    const config = previewConfig;
    if (!config?.ajaxUrl || !config?.nonce || !config?.action) {
      setState({
        html: '',
        isLoading: false,
        error: placeholderLabel,
      });
      return undefined;
    }

    const controller = new AbortController();
    const body = new FormData();
    body.append('action', config.action);
    body.append('nonce', config.nonce);
    body.append('module', metadata.name);
    body.append('attrs', attrsKey);

    setState((current) => ({
      ...current,
      isLoading: true,
      error: '',
    }));

    fetch(config.ajaxUrl, {
      body,
      method: 'POST',
      credentials: 'same-origin',
      signal: controller.signal,
    })
      .then((response) => response.json())
      .then((response) => {
        if (!response?.success) {
          throw new Error(response?.data?.message || placeholderLabel);
        }

        setState({
          html: response?.data?.html || '',
          isLoading: false,
          error: '',
        });
      })
      .catch((error) => {
        if (error.name === 'AbortError') {
          return;
        }

        setState({
          html: '',
          isLoading: false,
          error: error.message || placeholderLabel,
        });
      });

    return () => controller.abort();
  }, [attrsKey, metadata.name, placeholderLabel]);

  useEffect(() => {
    if (state.isLoading || state.error || !state.html) {
      return undefined;
    }

    const initSlider = () => {
      if (typeof window?.brBrandSliderInit === 'function') {
        window.brBrandSliderInit();
      }

      if (previewRef.current) {
        previewRef.current.dispatchEvent(
          new CustomEvent('brbrandDivi5PreviewLoaded', {
            bubbles: true,
            detail: {
              module: metadata.name,
            },
          }),
        );
      }
    };

    const frame = window.requestAnimationFrame(initSlider);

    return () => window.cancelAnimationFrame(frame);
  }, [state.html, state.isLoading, state.error, metadata.name]);

  if (state.isLoading) {
    return <div className="brbrand-divi5-vb-placeholder">Loading preview...</div>;
  }

  if (state.error) {
    return <div className="brbrand-divi5-vb-placeholder">{state.error}</div>;
  }

  return <div ref={previewRef} dangerouslySetInnerHTML={{ __html: state.html }} />;
};

export const createWidgetModule = (metadata, placeholderLabel) => ({
  metadata: normalizeMetadataFields(metadata),
  renderers: {
    edit: ({
      attrs,
      id,
      name,
      elements,
    }) => (
      <ModuleContainer
        attrs={attrs}
        elements={elements}
        id={id}
        moduleClassName={metadata.moduleClassName}
        name={name}
        scriptDataComponent={ModuleScriptData}
        stylesComponent={ModuleStyles}
        classnamesFunction={moduleClassnames}
      >
        {elements.styleComponents({
          attrName: 'module',
        })}
        <div className="et_pb_module_inner">
          <WidgetPreview
            attrs={attrs}
            metadata={metadata}
            placeholderLabel={placeholderLabel}
          />
        </div>
      </ModuleContainer>
    ),
  },
  placeholderContent: {
    module: {
      meta: {
        adminLabel: {
          desktop: {
            value: metadata.title,
          },
        },
      },
    },
    ...metadata.defaultAttrs,
  },
});
