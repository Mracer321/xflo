<x-error-shell code="403"
    title="Access denied"
    :message="$exception->getMessage() ?: 'You do not have permission to access this page.'" />
