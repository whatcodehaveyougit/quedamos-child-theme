/**
 * Editor registration for the quedamos/site-navigation block.
 *
 * This is the ONE JavaScript file in the theme that is not part of the Parcel
 * bundle. It is editor-only, so shipping it to visitors through
 * assets/scripts/scripts.js would be wrong, and it is written against the wp.*
 * globals WordPress already enqueues in the editor rather than against
 * @wordpress/* imports — which is what lets it run unbuilt and keeps Parcel out
 * of it entirely. Hence no JSX and no import statements: see
 * .claude/skills/writing-js/SKILL.md §5.
 *
 * Registering the block on the client is what puts it in the inserter and stops
 * the editor drawing it as an unrecognised block. The front end does not load
 * this file at all — render.php is the only thing visitors ever see.
 *
 * Metadata (title, icon, category, description, attributes) is deliberately NOT
 * repeated here. WordPress bootstraps it into the editor from the server-side
 * registration, which reads block.json — so block.json stays the single home for
 * it, exactly as it is for render.php.
 */

(function (blocks, blockEditor, components, element, i18n, data, serverSideRender) {
  const el = element.createElement;
  const __ = i18n.__;

  /**
   * The site's menus, for the picker.
   *
   * Returns null while the request is in flight, which is a different state from
   * "no menus exist" — the control shows a loading label rather than claiming the
   * site has none.
   *
   * @returns {Array|null} The wp_navigation records, or null until they arrive.
   */
  function useMenus() {
    return data.useSelect(function (select) {
      return select('core').getEntityRecords('postType', 'wp_navigation', {
        per_page: -1,
      });
    }, []);
  }

  /**
   * The menu picker's options.
   *
   * The empty value maps to no ref at all, which is the state render.php already
   * has an answer for: it falls back to the site's most recent menu. That is the
   * right default for a freshly inserted block, and it means a menu deleted out
   * from under the block degrades to the wrong menu rather than to nothing.
   *
   * @param {Array|null} menus The records from useMenus().
   * @returns {Array} SelectControl options.
   */
  function menuOptions(menus) {
    const options = [
      { value: '', label: __('Most recent menu', 'quedamos') },
    ];

    if (!menus) {
      return options;
    }

    menus.forEach(function (menu) {
      options.push({
        value: String(menu.id),
        label: menu.title && menu.title.rendered ? menu.title.rendered : __('(no title)', 'quedamos'),
      });
    });

    return options;
  }

  blocks.registerBlockType('quedamos/site-navigation', {
    edit: function (props) {
      const menus = useMenus();
      const blockProps = blockEditor.useBlockProps();

      const inspector = el(
        blockEditor.InspectorControls,
        null,
        el(
          components.PanelBody,
          { title: __('Menu', 'quedamos') },
          el(components.SelectControl, {
            label: __('Menu to render', 'quedamos'),
            help: __(
              'Both the desktop row and the mobile panel render from this one menu.',
              'quedamos'
            ),
            value: props.attributes.ref ? String(props.attributes.ref) : '',
            options: menuOptions(menus),
            onChange: function (value) {
              props.setAttributes({ ref: value ? parseInt(value, 10) : undefined });
            },
          })
        )
      );

      // The preview is the real render.php output, so the editor shows the block
      // as it will actually appear rather than a hand-maintained approximation
      // that drifts the first time the markup changes. It is inert in the canvas:
      // site-navigation.js never runs here, so the toggle does not open.
      const preview = el(serverSideRender, {
        block: 'quedamos/site-navigation',
        attributes: props.attributes,
      });

      return el('div', blockProps, inspector, preview);
    },

    // Dynamic block — the markup comes from render.php on every request, so
    // nothing is written into post content but the block comment itself.
    save: function () {
      return null;
    },
  });
})(
  window.wp.blocks,
  window.wp.blockEditor,
  window.wp.components,
  window.wp.element,
  window.wp.i18n,
  window.wp.data,
  window.wp.serverSideRender
);
