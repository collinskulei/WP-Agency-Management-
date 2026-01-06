Agency Services & Projects Manager

A robust, all-in-one WordPress solution for agencies that need to showcase what they do and who they've done it for. This plugin handles the heavy lifting of custom post types, filtering logic, and UI styling so you can focus on the content.

Features

Dual Custom Post Types: Dedicated sections for Services and Projects.

Smart Filtering: A project archive that filters by both Industry and Service without page reloads.

UI Customizer: Control colors, border radii, and typography directly from the WordPress dashboard.

Custom Font Support: Upload .woff or .ttf files directly to your media library to maintain brand consistency.

Shortcode Powered: Drop your grids anywhere using simple shortcodes.

Step-by-Step Guide

1. Installation

Download the agency_service_manager.php file.

Create a folder named agency-service-manager in your WordPress wp-content/plugins/ directory.

Drop the PHP file into that folder.

Go to your WordPress Dashboard > Plugins and click Activate.

2. Configure Your Branding

Before adding content, set up your look:

Navigate to the Agency Manager menu in your sidebar.

Set your primary colors (buttons/titles) and secondary colors (body text).

(Optional) Upload custom fonts to your Media Library and paste the URL in the typography section if you want to get fancy.

Set your Contact URL this is where your "Get Started" buttons will point.

3. Adding Services

Services are the "What we do" part of your agency.

Go to Services > Add New.

Add a title and a featured image.

Important: Fill out the SEO Summary in the metabox. This text appears on the archive grid.

The "Explore" Button: By default, it links to the service's single page. If you want it to link to an external landing page or a specific PDF, paste that URL in the Custom Redirect URL field.

4. Adding Projects

Projects are your "Case Studies."

Go to Projects > Add New.

Link to Service: In the sidebar/metabox, select which Service this project belongs to. This is crucial for the filtering logic.

Add Industry: Assign or create an Industry (e.g., "Fintech", "Healthcare") in the right-hand sidebar.

Fill out the SEO Summary and Live URL.

5. Displaying the Grids

To show your work to the world, use these shortcodes on any page or post:

For the Project Archive (with filters):
[agency_projects_grid]

For the Services Archive:
[agency_services_archive]

Technical Notes

Template Overriding: The plugin automatically takes over the layout for single Service and Project pages to ensure they match the plugin's aesthetic.

Page Builders: If you use Elementor or Beaver Builder on a Service page, the plugin will step aside and let the builder handle the layout.

Clean UI: The plugin hides unnecessary "Classic Editor" boxes to keep your editing experience focused.

License

MIT - Use it, break it, fix it, make it yours.
