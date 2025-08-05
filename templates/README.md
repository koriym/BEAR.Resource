# Semantic Portal Template

A comprehensive template for creating semantic portals that enable AI to understand your project's data structures and documentation without human explanation.

## What is a Semantic Portal?

A semantic portal implements Tim Berners-Lee's Semantic Web vision by providing:

- **JSON Schemas** - Machine-readable data structure definitions
- **ALPS Profile** - Semantic descriptors for HTML structure  
- **LLM Documentation** - Comprehensive technical documentation for AI consumption
- **Semantic Links** - Web standard links (`rel="describedby"`) connecting schemas to knowledge

## Quick Start

1. **Copy Template Files**
   ```bash
   cp -r templates/semantic-portal/* your-project-schemas/
   ```

2. **Follow the Generator Prompt**
   ```
   Copy templates/semantic-portal-generator-prompt.txt content and follow the instructions to customize for your project.
   ```

3. **Replace Placeholders**
   - Edit `index.html`, `schemas/alps.json`, and `llms-full.txt`
   - Replace all `{{PLACEHOLDER}}` values with your project details
   - Add your JSON schemas to the `schemas/` directory

4. **Test with AI**
   - Ask AI questions about your project's data structures
   - Verify that semantic links are discoverable and readable

## Template Structure

```
semantic-portal/
├── index.html              # Main portal page with ALPS-driven semantic classes
├── semantic-portal.css     # Universal CSS styling for semantic portals
├── schemas/
│   └── alps.json          # ALPS profile defining semantic descriptors
├── llms.txt               # Summary documentation for AI
├── llms-full.txt          # Comprehensive technical documentation
└── [your-schemas].json    # Your project's JSON schemas
```

## Key Features

- **Self-Contained**: All documentation embedded, no external dependencies
- **AI-Optimized**: Structured for machine understanding without human explanation
- **Web Standards**: Uses JSON Schema, ALPS, and RFC 8288 Link Relations
- **Universal**: Works with any AI system, not just specific platforms
- **Professional Design**: Clean, modern CSS with semantic color scheme
- **Responsive**: Works across all devices and screen sizes

## Examples

This template was used to create semantic portals for:
- [BEAR.Resource](https://bearsunday.github.io/BEAR.Resource/) - PHP hypermedia framework
- Resource profiling and performance analysis schemas
- Semantic logging structures for AI-powered analysis

## Usage with AI

Simply tell any AI:

> "Please analyze the project at [your-portal-url]. The schemas/ directory contains JSON schemas and llms-full.txt contains complete documentation. Follow the semantic links for additional context."

The AI will automatically discover and understand your project's structure, usage patterns, and integration guidelines.

## Contributing

This template represents a new paradigm for API documentation and project knowledge sharing. Contributions welcome for:

- Additional placeholder patterns
- Enhanced ALPS descriptors  
- Better CSS styling options
- Integration examples

## License

This template is part of the BEAR.Resource project and follows the same licensing terms.