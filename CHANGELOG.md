# 📦 Changelog

Todas as alterações notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado no [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).


# [1.1.0](https://github.com/heliomarpm/error-tracker-php/compare/v1.0.2...v1.1.0) (2026-10-28)


### Bug Fixes

* **core:** 🔒 blindar unserialize no cache e sanear logs do ErrorTracker ([8e4c1e3](https://github.com/heliomarpm/error-tracker-php/commit/8e4c1e34a8d2d805e1e1f01d9a29d5d0350727f0))


### Features

* **core:** ✨ adicionar método parameters e suporte a contexto global ([c7d0825](https://github.com/heliomarpm/error-tracker-php/commit/c7d0825feee500e27dfb0648ee6a9cfc23bc519d))
* **database:** ✨ adicionar persistência da coluna app_name no DatabaseHandler ([1fb2b2d](https://github.com/heliomarpm/error-tracker-php/commit/1fb2b2dc83d9113c104fb9e6ed636a130ea062de))
* **demo:** ✨ adicionar favicon svg moderno e fallback de rota ([560c4ec](https://github.com/heliomarpm/error-tracker-php/commit/560c4ec962ac93473944f03448f46cb2accae91f))
* **demo:** 🛡️ blindar roteador contra path traversal e isolar previews ([038e651](https://github.com/heliomarpm/error-tracker-php/commit/038e651a28befbd55be0616322f816a743e45232))
* **github:** ✨ externalizar templates de issues em arquivos .tpl ([903442a](https://github.com/heliomarpm/error-tracker-php/commit/903442a18316b78ead832859abdf33e45a48e186))
* **sanitizer:** 🛡️ reforçar salvaguardas de profundidade e dados do servidor ([b16ac57](https://github.com/heliomarpm/error-tracker-php/commit/b16ac57de3d20b9d4b529020f5ad1d03a2cb0995))


### Performance Improvements

* **core:** ⚡ otimizar I/O em TemplateRenderer e StackTraceFormatter ([9a87625](https://github.com/heliomarpm/error-tracker-php/commit/9a87625ab871d6226065dd8a9990eaa2cf322aeb))

## [1.0.2](https://github.com/heliomarpm/error-tracker-php/compare/v1.0.1...v1.0.2) (2026-10-25)


### Bug Fixes

* **vercel:** 🐛 criar ponto de entrada em api/ para deploy serverless ([e8b7d51](https://github.com/heliomarpm/error-tracker-php/commit/e8b7d51c093bd39bc35f746e7c09dbb2f6b44810))

## [1.0.1](https://github.com/heliomarpm/error-tracker-php/compare/v1.0.0...v1.0.1) (2026-10-25)


### Bug Fixes

* **ci:** 🐛 flexibilizar versao do pint para compatibilidade com php 8.2 ([3fe3804](https://github.com/heliomarpm/error-tracker-php/commit/3fe38049a422d56285ada2bdde9a55560e9fa75d))

# 1.0.0 (2026-10-25)


### Features

* **core:** ✨ adaptar templates e backtrace para logs informativos PSR-3 ([4d784a6](https://github.com/heliomarpm/error-tracker-php/commit/4d784a63b8f0f5e31cb9ca4db934d95ef301d14a))
* **core:** ✨ implementar sistema completo de rastreamento e reporte de erros ([0a78f0f](https://github.com/heliomarpm/error-tracker-php/commit/0a78f0fcd73e9aec4c592f940dd3ff0e6a91aaa7))
* **database:** ✨ enriquecer schema do handler e template de filelog ([7e01dc2](https://github.com/heliomarpm/error-tracker-php/commit/7e01dc26ef798abdbb37b214f2cb21cbbdbbb53b))
* **demo:** ✨ adicionar previews de filelog e database no visualizador ([af563f4](https://github.com/heliomarpm/error-tracker-php/commit/af563f478144a1891681f66cf210f6fbc386e016))
* **demo:** ✨ redesenhar interface em layout workbench com abas e guia de uso ([010048b](https://github.com/heliomarpm/error-tracker-php/commit/010048b971334a10030bbc4029906ef2b175245c))
* **formatters:** ✨ adicionar formatador de stack trace legível ([163aeeb](https://github.com/heliomarpm/error-tracker-php/commit/163aeeb946249e3fbc95a75056e4297f5637992e))
* **handlers:** ✨ padronizar filtro de severidade em todos os handlers ([ad79c49](https://github.com/heliomarpm/error-tracker-php/commit/ad79c4981c00ad69bba2810d7eba13c586878dab))
* initialize PHP error tracker library with core handlers, sanitization, and caching ([9995074](https://github.com/heliomarpm/error-tracker-php/commit/9995074dc55f51a1f17e21ad9ef6e1a2d3a55c08))
* **templates:** ✨ adicionar suporte a blocos condicionais e tags expandidas nos templates de e-mail ([f3fc68c](https://github.com/heliomarpm/error-tracker-php/commit/f3fc68c2de7244208f514d62f206f1fcd29371b1))
