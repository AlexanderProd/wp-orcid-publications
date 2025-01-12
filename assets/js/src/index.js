import { registerBlockType } from "@wordpress/blocks";
import {
  TextControl,
  PanelBody,
  SelectControl,
  RangeControl,
  ToggleControl,
} from "@wordpress/components";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import ServerSideRender from "@wordpress/server-side-render";

registerBlockType("orcid-publications/publications", {
  title: "ORCID Publications",
  icon: "book-alt",
  category: "widgets",
  attributes: {
    orcid: {
      type: "string",
      default: "",
    },
    titleTag: {
      type: "string",
      default: "h3",
    },
    fontSize: {
      type: "number",
      default: 16,
    },
    showYear: {
      type: "boolean",
      default: true,
    },
    showType: {
      type: "boolean",
      default: true,
    },
    layout: {
      type: "string",
      default: "list",
    },
  },
  edit: function (props) {
    const blockProps = useBlockProps();
    const { attributes, setAttributes } = props;

    return (
      <div {...blockProps}>
        <InspectorControls>
          <PanelBody title="Layout Settings">
            <SelectControl
              label="Layout Style"
              value={attributes.layout}
              options={[
                { label: "List", value: "list" },
                { label: "Grid", value: "grid" },
                { label: "Compact", value: "compact" },
              ]}
              onChange={(layout) => setAttributes({ layout })}
            />
            <SelectControl
              label="Title Tag"
              value={attributes.titleTag}
              options={[
                { label: "H2", value: "h2" },
                { label: "H3", value: "h3" },
                { label: "H4", value: "h4" },
              ]}
              onChange={(titleTag) => setAttributes({ titleTag })}
            />
            <RangeControl
              label="Font Size"
              value={attributes.fontSize}
              onChange={(fontSize) => setAttributes({ fontSize })}
              min={12}
              max={24}
            />
            <ToggleControl
              label="Show Year"
              checked={attributes.showYear}
              onChange={(showYear) => setAttributes({ showYear })}
            />
            <ToggleControl
              label="Show Type"
              checked={attributes.showType}
              onChange={(showType) => setAttributes({ showType })}
            />
          </PanelBody>
        </InspectorControls>

        <TextControl
          label="ORCID ID"
          value={attributes.orcid}
          onChange={(value) => setAttributes({ orcid: value })}
          help="Enter the ORCID ID (format: 0000-0000-0000-0000)"
        />
        {attributes.orcid && (
          <ServerSideRender
            block="orcid-publications/publications"
            attributes={attributes}
          />
        )}
      </div>
    );
  },
  save: function () {
    return null;
  },
});
