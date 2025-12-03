# Routes Refactoring - Option B Implementation
**Selected Option:** Keep "messages" but improve structure  
**Status:** Ready for Implementation

---

## 🎯 **Target Route Structure**

### **Before → After**

```diff
UI Routes (HTML/Twig):
- GET/POST /message/                    → /messages/create
- GET      /message/{accessToken}       → /messages/view/{accessToken}

API Routes (JSON):
- POST     /message/api/create          → /api/messages
- GET      /message/api/{accessToken}   → /api/messages/{accessToken}
- DELETE   /message/api/{accessToken}   → /api/messages/{accessToken}
```

---

## 📝 **Detailed Changes Required**

### **File 1: MessageController.php**

**Location:** `backend/src/Controller/MessageController.php`

#### **Change 1.1: Remove Controller-Level Route Prefix**

```php
// Line 15 - BEFORE:
#[Route('/message')]
class MessageController extends AbstractController

// Line 15 - AFTER:
#[Route('')]
class MessageController extends AbstractController
```

**Why:** Removing the prefix allows us to define full paths per method.

---

#### **Change 1.2: Update UI Route - Create Form**

```php
// Line 24 - BEFORE:
#[Route('/', name: 'app_message_new', methods: ['GET', 'POST'])]
public function new(Request $request): Response

// Line 24 - AFTER:
#[Route('/messages/create', name: 'app_messages_create_form', methods: ['GET', 'POST'])]
public function createForm(Request $request): Response
```

**Changes:**
- Route path: `/` → `/messages/create`
- Route name: `app_message_new` → `app_messages_create_form`
- Method name: `new()` → `createForm()`

---

#### **Change 1.3: Update UI Route - View Message**

```php
// Line 44 - BEFORE:
#[Route('/{accessToken}', name: 'app_message_show', methods: ['GET'])]
public function show(string $accessToken): Response

// Line 44 - AFTER:
#[Route('/messages/view/{accessToken}', name: 'app_messages_view', methods: ['GET'])]
public function view(string $accessToken): Response
```

**Changes:**
- Route path: `/{accessToken}` → `/messages/view/{accessToken}`
- Route name: `app_message_show` → `app_messages_view`
- Method name: `show()` → `view()`

---

#### **Change 1.4: Update API Route - Create**

```php
// Line 56 - BEFORE:
#[Route('/api/create', name: 'app_message_api_create', methods: ['POST'])]
public function apiCreate(Request $request): JsonResponse

// Line 56 - AFTER:
#[Route('/api/messages', name: 'api_messages_create', methods: ['POST'])]
public function apiCreate(Request $request): JsonResponse
```

**Changes:**
- Route path: `/api/create` → `/api/messages`
- Route name: `app_message_api_create` → `api_messages_create`
- Method name: stays `apiCreate()`

---

#### **Change 1.5: Update API Route - Show**

```php
// Line 80 - BEFORE:
#[Route('/api/{accessToken}', name: 'app_message_api_show', methods: ['GET'])]
public function apiShow(string $accessToken, Request $request): JsonResponse

// Line 80 - AFTER:
#[Route('/api/messages/{accessToken}', name: 'api_messages_show', methods: ['GET'])]
public function apiShow(string $accessToken, Request $request): JsonResponse
```

**Changes:**
- Route path: `/api/{accessToken}` → `/api/messages/{accessToken}`
- Route name: `app_message_api_show` → `api_messages_show`
- Method name: stays `apiShow()`

---

#### **Change 1.6: Update API Route - Delete**

```php
// Line 112 - BEFORE:
#[Route('/api/{accessToken}', name: 'app_message_api_delete', methods: ['DELETE'])]
public function apiDelete(string $accessToken, Request $request): JsonResponse

// Line 112 - AFTER:
#[Route('/api/messages/{accessToken}', name: 'api_messages_delete', methods: ['DELETE'])]
public function apiDelete(string $accessToken, Request $request): JsonResponse
```

**Changes:**
- Route path: `/api/{accessToken}` → `/api/messages/{accessToken}`
- Route name: `app_message_api_delete` → `api_messages_delete`
- Method name: stays `apiDelete()`

---

#### **Change 1.7: Update Redirect Call**

```php
// Line 35 - BEFORE:
return $this->redirectToRoute('app_message_show', ['accessToken' => $message->getAccessToken()], Response::HTTP_SEE_OTHER);

// Line 35 - AFTER:
return $this->redirectToRoute('app_messages_view', ['accessToken' => $message->getAccessToken()], Response::HTTP_SEE_OTHER);
```

**Changes:**
- Route name in redirect: `app_message_show` → `app_messages_view`

---

### **File 2: Twig Templates**

#### **Template: backend/templates/message/create.html.twig**

Search for route references and update:

```twig
{# BEFORE #}
{{ path('app_message_new') }}
{{ path('app_message_show', {'accessToken': token}) }}

{# AFTER #}
{{ path('app_messages_create_form') }}
{{ path('app_messages_view', {'accessToken': token}) }}
```

---

#### **Template: backend/templates/message/show.html.twig**

Search for route references and update:

```twig
{# BEFORE #}
{{ path('app_message_new') }}
{{ path('app_message_show', {'accessToken': token}) }}

{# AFTER #}
{{ path('app_messages_create_form') }}
{{ path('app_messages_view', {'accessToken': token}) }}
```

---

#### **Template: backend/templates/base.html.twig**

Check for any navigation links or route references:

```twig
{# BEFORE #}
<a href="{{ path('app_message_new') }}">Create Message</a>

{# AFTER #}
<a href="{{ path('app_messages_create_form') }}">Create Message</a>
```

---

### **File 3: Frontend JavaScript (if exists)**

#### **File: backend/public/js/encryption.js**

Update API endpoint URLs:

```javascript
// BEFORE
fetch('/message/api/create', {
    method: 'POST',
    // ...
});

fetch('/message/api/' + accessToken, {
    method: 'GET',
    // ...
});

// AFTER
fetch('/api/messages', {
    method: 'POST',
    // ...
});

fetch('/api/messages/' + accessToken, {
    method: 'GET',
    // ...
});
```

---

### **File 4: Documentation Updates**

#### **README.md**

Update all route examples:

```markdown
<!-- BEFORE -->
- Create: POST /message/api/create
- View: GET /message/api/{token}

<!-- AFTER -->
- Create: POST /api/messages
- View: GET /api/messages/{token}
```

---

#### **INFRASTRUCTURE.md**

Update route documentation:

```markdown
<!-- BEFORE -->
## Routes
- HTML routes: `/message/*` (Twig templates)
- API routes: `/message/api/*` (JSON endpoints)

<!-- AFTER -->
## Routes
- HTML routes: `/messages/*` (Twig templates)
- API routes: `/api/messages/*` (JSON endpoints)
```

---

#### **PRODUCTION_DEPLOYMENT.md**

Update any route examples in deployment guide.

---

## 🔧 **Post-Implementation Steps**

### **1. Clear Symfony Cache**

```bash
docker compose exec php php bin/console cache:clear --env=dev
```

### **2. Verify Routes**

```bash
# List all routes
docker compose exec php php bin/console debug:router

# Should see:
# app_messages_create_form     GET|POST   /messages/create
# app_messages_view            GET        /messages/view/{accessToken}
# api_messages_create          POST       /api/messages
# api_messages_show            GET        /api/messages/{accessToken}
# api_messages_delete          DELETE     /api/messages/{accessToken}
```

### **3. Test All Routes**

**UI Routes:**
```bash
# Test create form loads
curl -I http://localhost/messages/create

# Test view page (with valid token)
curl -I http://localhost/messages/view/abc123
```

**API Routes:**
```bash
# Test API create
curl -X POST http://localhost/api/messages \
  -H "Content-Type: application/json" \
  -d '{"content":"test","keyHash":"hash","expiresAt":"2025-12-31T23:59:59Z"}'

# Test API show (need valid token and keyHash)
curl http://localhost/api/messages/abc123?keyHash=hash123
```

---

## ✅ **Verification Checklist**

Before considering the refactoring complete:

- [ ] All 6 routes in MessageController updated
- [ ] Method names updated (new → createForm, show → view)
- [ ] Redirect route name updated
- [ ] Template files checked and updated
- [ ] JavaScript files checked and updated
- [ ] Documentation files updated
- [ ] Symfony cache cleared
- [ ] Routes verified with debug:router
- [ ] Manual testing of all routes completed
- [ ] No 404 errors in logs

---

## 🔄 **Backwards Compatibility (Optional)**

If you want to support old URLs temporarily, add these legacy routes:

```php
// Add at the end of MessageController class

// Legacy redirects - remove after transition period
#[Route('/message/', name: 'legacy_message_new', methods: ['GET', 'POST'])]
public function legacyNew(): Response
{
    return $this->redirectToRoute('app_messages_create_form', [], 301);
}

#[Route('/message/{accessToken}', name: 'legacy_message_show', methods: ['GET'])]
public function legacyShow(string $accessToken): Response
{
    return $this->redirectToRoute('app_messages_view', ['accessToken' => $accessToken], 301);
}

#[Route('/message/api/create', name: 'legacy_api_create', methods: ['POST'])]
public function legacyApiCreate(): JsonResponse
{
    return new JsonResponse([
        'error' => 'Endpoint moved',
        'new_url' => '/api/messages',
        'message' => 'Please update your API client to use the new endpoint'
    ], 301);
}

#[Route('/message/api/{accessToken}', name: 'legacy_api_show', methods: ['GET', 'DELETE'])]
public function legacyApiRoute(string $accessToken): JsonResponse
{
    return new JsonResponse([
        'error' => 'Endpoint moved',
        'new_url' => '/api/messages/' . $accessToken,
        'message' => 'Please update your API client to use the new endpoint'
    ], 301);
}
```

---

## 📊 **Route Comparison**

### **Complete Before/After Overview**

| Type | HTTP | Old Route | New Route |
|------|------|-----------|-----------|
| UI | GET/POST | `/message/` | `/messages/create` |
| UI | GET | `/message/{token}` | `/messages/view/{token}` |
| API | POST | `/message/api/create` | `/api/messages` |
| API | GET | `/message/api/{token}` | `/api/messages/{token}` |
| API | DELETE | `/message/api/{token}` | `/api/messages/{token}` |

### **Route Names Before/After**

| Old Name | New Name |
|----------|----------|
| `app_message_new` | `app_messages_create_form` |
| `app_message_show` | `app_messages_view` |
| `app_message_api_create` | `api_messages_create` |
| `app_message_api_show` | `api_messages_show` |
| `app_message_api_delete` | `api_messages_delete` |

---

## ⚠️ **Breaking Changes**

**What will break:**
- ✅ Old bookmarked URLs (`/message/{token}`)
- ✅ External API clients using old endpoints
- ✅ Any hardcoded URLs in code

**What won't break:**
- ✅ Database (no changes)
- ✅ Business logic (only routes change)
- ✅ Docker setup (no changes)

---

## ⏱️ **Implementation Time Estimate**

- **File changes:** 20 minutes
- **Testing:** 15 minutes
- **Documentation:** 10 minutes
- **Total:** ~45 minutes

---

## 🚀 **Ready to Implement?**

All changes are documented above. Reply with "yes" or "go" to proceed with implementation!

