# Mautic Advanced Templates Bundle

Plugin extends default email template capabilities with TWIG block so you can use advanced templating techniques like conditions, loops etc.

### Purpose

For example, you need a slightly different content of your email depending on the information you already know about your contact (e.g., country, gender, whatever). Instead of creating tons of very similar emails, you can create one with conditions coded inside.

Another example: you might want to include dynamic content to your email. Let's say you are implementing an Abandoned Cart feature and you want your customers to see exact content of their cart. Again, the solution might be to push cart content in JSON format to your contact via API and then iterate through the items in your template to render picture, name and price for each one.

### Compatibility

This plugin was tested with:

* Mautic v2.14.2
* PHP v7.1.23

There is a high probability it is compatible with other environments, but we never tested it.

### Features

* TWIG templates could be used in the emails. Just create an email and put your TWIG template between special tags:
    ```twig
    {% TWIG_BLOCK %} 
    Your template TWIG goes here....                                        
    {% END_TWIG_BLOCK %}
    ```
* Reusable TWIG snippets could be loaded form Dynamic Content entities.
* TWIG extended with some useful functions and filters (see below).
* RSS support
* RSS items related to contact's segment preferences center and RSS category    

## Installation

1. Download or clone this bundle into your Mautic `/plugins` folder.
2. Delete your cache (`app/cache/prod`).
3. In the Mautic GUI, go to the gear and then to Plugins.
4. Click "Install/Upgrade Plugins".
5. You should see the Advanced Templates Bundle in your list of plugins.


## Usage

Once installed, the plugin is ready to be used (no configuration required).
Shortly saying, the text between `{% TWIG_BLOCK %}` and `{% END_TWIG_BLOCK %}` in your emails will be treated as a TWIG template. Please check out [TWIG official documentation](https://twig.symfony.com/doc/2.x/templates.html) to familiarize yourself with syntax and capabilities.

You can also avoid lots of copy-and-paste with `include()` function available in templates. Just put reusable pieces of templates into Dynamic Content entity and use it in your main email templates (see examples below). 

Note: The context will be shared with included template so each variable available outside will be available in the included snippet.   

### Context

The table below explains which variables are exposed to the context. Also it contains the list of extra functions and filters available. Please note that all standard library of tags\filter\functions as per official TWIG documents is available as well.

| Entity      | Type     | Description                              | Example                                  |
| ----------- | -------- | ---------------------------------------- | ---------------------------------------- |
| lead        | Variable | Holds a Lead entity (contact). You should refer fields by alias name (see example). | `{{lead.firstname}}`, `{{lead.country}}` |
| json_decode | Filter   | Converts string in JSON format into object. | `{% set cart = lead.cart | json_decode %}` In this sample we declare variable `cart` which will hold deserialized cart. |


### Example 1: Basic scenario

Let's say you'd like to add an extra paragraph about weather in New York for people from that area:  

1. Navigate to the Channels / Emails / New / Builder
2. Open the editor for the slot you need to update (Source code mode is preferable)
3. Put the following inside your template:
    ```twig
    {% TWIG_BLOCK %} 
        <p>Hi {{lead.firstname}},</p>
        {% if lead.city == 'New York' %}
            <p>What a great weather is in New York this week!</p>
        {% endif %}
        
        <p>Main letter content goes here</p>         
    {% END_TWIG_BLOCK %}
    ```

### Example 2: Rendering structured data

Imaging you need to remind your prospect about incomplete purchase (Abandoned Cart feature).

We assume you have an integration with your e-commerce software which pushes cart information into Mautic contact entity in the custom field `cart`. 

Assume cart information is JSON and has the following format:

```json
  [
    {"sku": "123456", "name": "My cool product 1"},
    {"sku": "8574865", "name": "My cool product 2"}
  ]
```

Thus, in order to render all items, you should code something like this: 

```twig
{% TWIG_BLOCK %} 
    {% set cart = lead.cart | json_decode %}     
    Your cart:
    <ul> 
    {% for item in cart %}
      <li>Item Name: {{ item.name }}</li>
    {% endfor %}
    </ul>             
{% END_TWIG_BLOCK %}
```

### Example 3: Reusable code snippets

It might happen you need similar blocks to be included into multiple emails. In this case, it is a good idea to improve maintainability and keep common pieces in a single place. The solution this bundle offers is to leverage Dynamic Content entity and TWIG built-in function `include()`. 

Let's continue with the previous example but turn template for rendering a single item into a reusable snippet.

1. Navigate to Components / Dynamic Content
1. Create new entity with name `email-cart-item`.
1. Put the following into Content area:
    ```twig
    <li>Sku: {{ item.sku }}, Name: {{ item.name }}.</li>
    ```
1. Update your email template with the following:
    ```twig
    {% TWIG_BLOCK %} 
        {% set cart = lead.cart | json_decode %}     
        Your cart:
        <ul> 
        {% for item in cart %}
          {{ include('dc:email-cart-item') }}
        {% endfor %}
        </ul>             
    {% END_TWIG_BLOCK %}
    ```
    Notice prefix `dc:` which instructs template resolver to look for dynamic content instance.
    
### Example 4: RSS support    
    
```twig
     {% TWIG_BLOCK %} 
          {% set items = 'http://domain.tld/feed/' | rss %}     
          <ul> 
          {% for item in items %}
              <li>
               <a href=''{{ item.link }}'>{{ item.title }}</a> ({{ item.pubDate|date('m/d/Y') }})
               <br />{{ item.description|raw }}
               </li>
          {% endfor %}
          </ul>             
      {% END_TWIG_BLOCK %}
```
        
    
 ### Example 5: RSS related items to contact's segments

- Add one or more categories to item 
https://www.w3schools.com/xml/rss_tag_category_item.asp 
- Each contact receive personalized items based on segment assignemnt.
- Matching between item categories and segment aliases
        
```twig
        {% TWIG_BLOCK %} 
            {% set items = 'http://domain.tld/feed/' | rss('segments') %}     
            <ul> 
            {% for item in items %}
                <li>
                 <a href=''{{ item.link }}'>{{ item.title }}</a> ({{ item.pubDate|date('m/d/Y') }})
                 <br />{{ item.description|raw }}
                 </li>
            {% endfor %}
            </ul>             
        {% END_TWIG_BLOCK %}
```

## Credits

Dmitry Berezovsky, Logicify ([http://logicify.com/](https://logicify.com/?utm_source=github&utm_campaign=mautic-templates&utm_medium=opensource))

## Disclaimer

This plug-in is licensed under MIT. This means you are free to use it even in commercial projects.

The MIT license clearly explains that there is no warranty for this free software. 
Please see the included [LICENSE](LICENSE) file for details.
