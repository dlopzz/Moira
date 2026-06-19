# Moira — Diagramas de arquitectura

---

## 0. Flujo completo: crear un Customer (archivo por archivo)

```mermaid
flowchart TD
    subgraph FE["🌐 moira-web (Next.js)"]
        A["📄 app/auth/register/page.tsx\n─────────────────────\nEl usuario llena el form:\nfirst_name, last_name,\nemail, date_of_birth,\npassword, password_confirmation\n\nhandleSubmit() → llama a api.register()"]

        B["📄 lib/api.ts\n─────────────────────\napi.register(form)\n→ fetch POST /api/v1/auth/register\n   Content-Type: application/json\n   body: JSON.stringify(form)"]

        Z["📄 lib/auth.ts\n─────────────────────\nsaveToken(res.token)\n→ localStorage.setItem('token', ...)"]

        A -->|"handleSubmit()"| B
    end

    subgraph BE["⚙️ moira-api (Laravel)"]
        C["📄 routes/api.php  :line 47\n─────────────────────\nRoute::post(\n  'auth/register',\n  RegisterController::class, 'store'\n)"]

        D["📄 Http/Requests/Api/Auth/RegisterRequest.php\n─────────────────────\nreglas de validación:\n• first_name  → required, max 100\n• last_name   → required, max 100\n• email       → required, unique:customers\n• date_of_birth → required, before:-13 years\n• password    → min 8, confirmed,\n                letras + números (regex)"]

        E["📄 Http/Controllers/Api/V1/Auth/RegisterController.php\n─────────────────────\nstore(RegisterRequest $request)\n→ Customer::create({...})\n→ $customer->createToken('api')\n→ return 201 JSON"]

        F["📄 app/Models/Customer.php\n─────────────────────\nextends Authenticatable\nuse HasApiTokens  ← permite crear tokens\nuse HasFactory, SoftDeletes, Notifiable\n\nCustomer::create() →\n  'password' se hashea automáticamente\n  por el cast 'hashed'"]

        G["📄 Http/Resources/Api/CustomerResource.php\n─────────────────────\nFormatea el objeto Customer\npara la respuesta JSON:\n  id, first_name, last_name,\n  name, email, dob,\n  is_active, created_at\n\n❌ NO incluye password"]

        C -->|"1. llega el request"| D
        D -->|"2. validación OK"| E
        D -->|"2b. validación FALLA\n→ 422 + errores por campo"| FAIL["❌ 422 Unprocessable\n{ errors: {\n  email: ['ya existe'],\n  password: ['...'],\n  ...\n} }"]
        E -->|"3. crea el Customer"| F
        F -->|"4. INSERT customers"| DB1[("🗄️ tabla: customers\n─────────────\nnueva fila con\nid, first_name, last_name,\nemail, password_hash,\ndate_of_birth, is_active")]
        E -->|"5. crea el token\ncreateToken('api')"| DB2[("🗄️ tabla: personal_access_tokens\n─────────────\ntokenable_type: Customer\ntokenable_id: 42\ntoken: hash_del_token\nname: 'api'")]
        E -->|"6. formatea respuesta"| G
    end

    B -->|"HTTP POST\n/api/v1/auth/register"| C

    G -->|"7. responde 201\n{ data: Customer,\n  token: '42|xyzABC...' }"| RESP["📦 Respuesta JSON\n─────────────\n{\n  data: {\n    id: 42,\n    name: 'Daniel López',\n    email: 'daniel@...',\n    ...\n  },\n  token: '42|xyzABC...'\n}"]

    RESP -->|"8. back al frontend"| B2["📄 lib/api.ts\nrecibe la respuesta\nretorna { data, token }"]

    B2 -->|"9. saveToken(res.token)"| Z
    Z -->|"10. router.push('/profile')"| END["✅ Usuario logueado\nredirige a /profile\n\nTodos los requests futuros:\nAuthorization: Bearer 42|xyzABC..."]

    style FAIL fill:#fee2e2,stroke:#ef4444,color:#991b1b
    style DB1 fill:#dbeafe,stroke:#3b82f6,color:#1e40af
    style DB2 fill:#dbeafe,stroke:#3b82f6,color:#1e40af
    style END fill:#dcfce7,stroke:#22c55e,color:#166534
    style RESP fill:#fef9c3,stroke:#eab308,color:#713f12
```

---

## 1. Arquitectura general

```mermaid
graph TB
    subgraph BROWSER["🌐 Navegador del cliente"]
        NX["Next.js 16\n(React + TypeScript)"]
        LS[("localStorage\ntoken Bearer")]
        NX <--> LS
    end

    subgraph ADMIN["🖥️ Panel de administración"]
        FIL["Filament v4\n(PHP declarativo)"]
        LW["Livewire v3\n(reactividad sin JS manual)"]
        ALP["Alpine.js\n(micro-interacciones)"]
        FIL --> LW
        FIL --> ALP
    end

    subgraph API["⚙️ Laravel 13 — moira-api"]
        direction TB
        RT["routes/api.php\n(todas las rutas /api/v1/)"]
        SANC["Middleware auth:sanctum\n(valida Bearer token)"]
        CTRL["Controllers V1/\n(lógica de negocio)"]
        REQ["Form Requests\n(validación)"]
        RES["API Resources\n(formato del JSON)"]
        MOD["Eloquent Models\n(Customer, Product, Order…)"]
        SVC["Services\n(PayWayProvider, AndreaniProvider)"]
        MAIL["Mail + Queue\n(Redis)"]

        RT --> SANC --> CTRL
        CTRL --> REQ
        CTRL --> MOD
        CTRL --> SVC
        CTRL --> RES
        CTRL --> MAIL
    end

    subgraph DB["🗄️ Datos"]
        PG[("PostgreSQL\ntodas las tablas")]
        RD[("Redis\ncola de emails")]
        PAT[("personal_access_tokens\ntokens Sanctum")]
    end

    subgraph EXT["☁️ Externos"]
        PW["PayWay / Decidir\n(tokenización + cobro)"]
        AND["Andreani\n(cotización de envío)"]
    end

    NX -- "POST /api/v1/auth/register\nGET  /api/v1/products\nAuthorization: Bearer token" --> RT
    ADMIN -- "/admin (Filament routes)" --> MOD
    MOD <--> PG
    SANC <--> PAT
    MAIL --> RD
    SVC -- "HTTPS" --> PW
    SVC -- "HTTPS" --> AND
    NX -- "JS SDK (tokeniza tarjeta)" --> PW
```

---

## 2. Flujo: registro de un nuevo cliente

```mermaid
sequenceDiagram
    actor U as 👤 Usuario
    participant F as Next.js<br/>/auth/register
    participant LS as localStorage
    participant R as routes/api.php
    participant RQ as RegisterRequest<br/>(validación)
    participant C as RegisterController
    participant DB as PostgreSQL<br/>(customers)
    participant ST as personal_access_tokens

    U->>F: Completa el form<br/>(nombre, email, password)
    F->>R: POST /api/v1/auth/register<br/>Content-Type: application/json
    R->>RQ: Valida los datos
    alt Validación falla
        RQ-->>F: 422 { errors: { email: ["ya existe"] } }
        F-->>U: Muestra errores en el form
    else Validación OK
        RQ->>C: Datos validados
        C->>DB: Customer::create({...})<br/>INSERT en tabla customers
        DB-->>C: Customer con id=42
        C->>ST: $customer->createToken('api')<br/>INSERT en personal_access_tokens
        ST-->>C: plainTextToken = "42|xyzABC..."
        C-->>R: 201 { data: Customer, token: "42|xyzABC..." }
        R-->>F: 201 JSON
        F->>LS: localStorage.setItem('token', '42|xyzABC...')
        F-->>U: Redirige a /profile
    end
```

---

## 3. Flujo: request autenticado (ej. ver perfil)

```mermaid
sequenceDiagram
    actor U as 👤 Usuario ya logueado
    participant F as Next.js<br/>/profile
    participant LS as localStorage
    participant MW as auth:sanctum<br/>middleware
    participant ST as personal_access_tokens
    participant C as ProfileController
    participant DB as PostgreSQL

    U->>F: Navega a /profile
    F->>LS: getItem('token')
    LS-->>F: "42|xyzABC..."
    F->>MW: GET /api/v1/profile<br/>Authorization: Bearer 42|xyzABC...
    MW->>ST: Busca token hasheado
    alt Token no existe o expiró
        ST-->>MW: null
        MW-->>F: 401 Unauthenticated
        F-->>U: Redirige a /auth/login
    else Token válido
        ST-->>MW: customer_id = 42
        MW->>DB: SELECT * FROM customers WHERE id = 42
        DB-->>MW: Customer hidratado
        MW->>C: $request->user() = Customer#42
        C->>DB: Consultas adicionales si las hay
        DB-->>C: Datos
        C-->>F: 200 { data: { id: 42, name: "Daniel López", ... } }
        F-->>U: Muestra la página de perfil
    end
```

---

## 4. Flujo: checkout con pago con tarjeta

```mermaid
sequenceDiagram
    actor U as 👤 Cliente
    participant F as Next.js<br/>/checkout/payment
    participant SDK as PayWay JS SDK<br/>(ventasonline.payway.com.ar)
    participant API as Laravel API
    participant PW as PayWay Backend<br/>(api.payway.com.ar)
    participant DB as PostgreSQL

    U->>F: Ingresa datos de tarjeta
    Note over F: Los datos de tarjeta NUNCA<br/>llegan al servidor de Moira

    F->>SDK: sdk.createToken(form)<br/>con public_key
    SDK->>PW: POST /tokens<br/>{ card_number, expiry, cvv }
    PW-->>SDK: { token: "abc", bin: "450799", payment_method_id: 1 }
    SDK-->>F: callback(200, tokenResponse)

    F->>API: POST /api/v1/checkout/pay<br/>Bearer token<br/>{ token, bin, installments, ... }
    Note over API: La private_key NUNCA<br/>sale del servidor

    API->>PW: POST /api/v2/payments<br/>Authorization: Basic base64(private_key:)<br/>{ token, amount, ... }
    PW-->>API: { status: "approved", id: "txn_123" }

    API->>DB: INSERT orders + decrement stock
    DB-->>API: Order#99
    API-->>F: 201 { data: { number: "ORD-0099" } }
    F-->>U: Redirige a /checkout/success
```

---

## 5. Cómo funciona Filament (panel admin)

```mermaid
graph LR
    subgraph PHP["PHP — definición declarativa"]
        PF["ProductForm.php\nTextInput::make('name')\nSelect::make('categories')\nSection::make('SEO')..."]
        PT["ProductsTable.php\nTextColumn::make('name')\nBadgeColumn::make('stock')..."]
        PR["ProductResource.php\nnavigationIcon, model, pages..."]
    end

    subgraph FIL["Filament v4 (interpreta el PHP)"]
        LW["Livewire\n(componentes reactivos)"]
        AL["Alpine.js\n(toggles, dropdowns)"]
        TW["Tailwind CSS\n(estilos)"]
    end

    subgraph HTML["Lo que ve el admin en /admin"]
        HF["Formulario con campos,\nvalidación en vivo,\nfile uploads, etc."]
        HT["Tabla con búsqueda,\nfiltros, paginación,\nacciones bulk"]
    end

    PR --> LW
    PF --> LW
    PT --> LW
    LW --> AL
    LW --> TW
    LW --> HF
    LW --> HT
```

---

## 6. Estructura de carpetas clave

```mermaid
graph TD
    ROOT["moira/"]

    ROOT --> API["moira-api/ (Laravel)"]
    ROOT --> WEB["moira-web/ (Next.js)"]

    API --> AR["app/"]
    AR --> ARC["Http/Controllers/Api/V1/\n→ un controller por recurso"]
    AR --> ARR["Http/Resources/Api/\n→ formatean el JSON de salida"]
    AR --> ARQ["Http/Requests/Api/\n→ validan el JSON de entrada"]
    AR --> ARM["Models/\nCustomer, Product, Order..."]
    AR --> ARS["Services/\nPayWayProvider, AndreaniProvider"]
    AR --> ARF["Filament/Resources/\n→ panel de admin"]

    WEB --> WS["src/"]
    WS --> WSA["app/\n→ páginas Next.js (App Router)"]
    WS --> WSL["lib/api.ts\n→ todas las llamadas HTTP"]
    WS --> WSC["components/\n→ Header, ProductCard..."]
```
