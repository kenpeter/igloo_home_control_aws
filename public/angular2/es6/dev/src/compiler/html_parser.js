var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
import { isPresent, isBlank } from 'angular2/src/facade/lang';
import { ListWrapper } from 'angular2/src/facade/collection';
import { HtmlAttrAst, HtmlTextAst, HtmlCommentAst, HtmlElementAst } from './html_ast';
import { Injectable } from 'angular2/src/core/di';
import { HtmlTokenType, tokenizeHtml } from './html_lexer';
import { ParseError, ParseSourceSpan } from './parse_util';
import { getHtmlTagDefinition, getNsPrefix, mergeNsAndName } from './html_tags';
export class HtmlTreeError extends ParseError {
    constructor(elementName, span, msg) {
        super(span, msg);
        this.elementName = elementName;
    }
    static create(elementName, span, msg) {
        return new HtmlTreeError(elementName, span, msg);
    }
}
export class HtmlParseTreeResult {
    constructor(rootNodes, errors) {
        this.rootNodes = rootNodes;
        this.errors = errors;
    }
}
export let HtmlParser = class {
    parse(sourceContent, sourceUrl) {
        var tokensAndErrors = tokenizeHtml(sourceContent, sourceUrl);
        var treeAndErrors = new TreeBuilder(tokensAndErrors.tokens).build();
        return new HtmlParseTreeResult(treeAndErrors.rootNodes, tokensAndErrors.errors
            .concat(treeAndErrors.errors));
    }
};
HtmlParser = __decorate([
    Injectable(), 
    __metadata('design:paramtypes', [])
], HtmlParser);
class TreeBuilder {
    constructor(tokens) {
        this.tokens = tokens;
        this.index = -1;
        this.rootNodes = [];
        this.errors = [];
        this.elementStack = [];
        this._advance();
    }
    build() {
        while (this.peek.type !== HtmlTokenType.EOF) {
            if (this.peek.type === HtmlTokenType.TAG_OPEN_START) {
                this._consumeStartTag(this._advance());
            }
            else if (this.peek.type === HtmlTokenType.TAG_CLOSE) {
                this._consumeEndTag(this._advance());
            }
            else if (this.peek.type === HtmlTokenType.CDATA_START) {
                this._closeVoidElement();
                this._consumeCdata(this._advance());
            }
            else if (this.peek.type === HtmlTokenType.COMMENT_START) {
                this._closeVoidElement();
                this._consumeComment(this._advance());
            }
            else if (this.peek.type === HtmlTokenType.TEXT ||
                this.peek.type === HtmlTokenType.RAW_TEXT ||
                this.peek.type === HtmlTokenType.ESCAPABLE_RAW_TEXT) {
                this._closeVoidElement();
                this._consumeText(this._advance());
            }
            else {
                // Skip all other tokens...
                this._advance();
            }
        }
        return new HtmlParseTreeResult(this.rootNodes, this.errors);
    }
    _advance() {
        var prev = this.peek;
        if (this.index < this.tokens.length - 1) {
            // Note: there is always an EOF token at the end
            this.index++;
        }
        this.peek = this.tokens[this.index];
        return prev;
    }
    _advanceIf(type) {
        if (this.peek.type === type) {
            return this._advance();
        }
        return null;
    }
    _consumeCdata(startToken) {
        this._consumeText(this._advance());
        this._advanceIf(HtmlTokenType.CDATA_END);
    }
    _consumeComment(token) {
        var text = this._advanceIf(HtmlTokenType.RAW_TEXT);
        this._advanceIf(HtmlTokenType.COMMENT_END);
        var value = isPresent(text) ? text.parts[0].trim() : null;
        this._addToParent(new HtmlCommentAst(value, token.sourceSpan));
    }
    _consumeText(token) {
        let text = token.parts[0];
        if (text.length > 0 && text[0] == '\n') {
            let parent = this._getParentElement();
            if (isPresent(parent) && parent.children.length == 0 &&
                getHtmlTagDefinition(parent.name).ignoreFirstLf) {
                text = text.substring(1);
            }
        }
        if (text.length > 0) {
            this._addToParent(new HtmlTextAst(text, token.sourceSpan));
        }
    }
    _closeVoidElement() {
        if (this.elementStack.length > 0) {
            let el = ListWrapper.last(this.elementStack);
            if (getHtmlTagDefinition(el.name).isVoid) {
                this.elementStack.pop();
            }
        }
    }
    _consumeStartTag(startTagToken) {
        var prefix = startTagToken.parts[0];
        var name = startTagToken.parts[1];
        var attrs = [];
        while (this.peek.type === HtmlTokenType.ATTR_NAME) {
            attrs.push(this._consumeAttr(this._advance()));
        }
        var fullName = getElementFullName(prefix, name, this._getParentElement());
        var selfClosing = false;
        // Note: There could have been a tokenizer error
        // so that we don't get a token for the end tag...
        if (this.peek.type === HtmlTokenType.TAG_OPEN_END_VOID) {
            this._advance();
            selfClosing = true;
            if (getNsPrefix(fullName) == null && !getHtmlTagDefinition(fullName).isVoid) {
                this.errors.push(HtmlTreeError.create(fullName, startTagToken.sourceSpan, `Only void and foreign elements can be self closed "${startTagToken.parts[1]}"`));
            }
        }
        else if (this.peek.type === HtmlTokenType.TAG_OPEN_END) {
            this._advance();
            selfClosing = false;
        }
        var end = this.peek.sourceSpan.start;
        var el = new HtmlElementAst(fullName, attrs, [], new ParseSourceSpan(startTagToken.sourceSpan.start, end));
        this._pushElement(el);
        if (selfClosing) {
            this._popElement(fullName);
        }
    }
    _pushElement(el) {
        if (this.elementStack.length > 0) {
            var parentEl = ListWrapper.last(this.elementStack);
            if (getHtmlTagDefinition(parentEl.name).isClosedByChild(el.name)) {
                this.elementStack.pop();
            }
        }
        var tagDef = getHtmlTagDefinition(el.name);
        var parentEl = this._getParentElement();
        if (tagDef.requireExtraParent(isPresent(parentEl) ? parentEl.name : null)) {
            var newParent = new HtmlElementAst(tagDef.parentToAdd, [], [el], el.sourceSpan);
            this._addToParent(newParent);
            this.elementStack.push(newParent);
            this.elementStack.push(el);
        }
        else {
            this._addToParent(el);
            this.elementStack.push(el);
        }
    }
    _consumeEndTag(endTagToken) {
        var fullName = getElementFullName(endTagToken.parts[0], endTagToken.parts[1], this._getParentElement());
        if (getHtmlTagDefinition(fullName).isVoid) {
            this.errors.push(HtmlTreeError.create(fullName, endTagToken.sourceSpan, `Void elements do not have end tags "${endTagToken.parts[1]}"`));
        }
        else if (!this._popElement(fullName)) {
            this.errors.push(HtmlTreeError.create(fullName, endTagToken.sourceSpan, `Unexpected closing tag "${endTagToken.parts[1]}"`));
        }
    }
    _popElement(fullName) {
        for (let stackIndex = this.elementStack.length - 1; stackIndex >= 0; stackIndex--) {
            let el = this.elementStack[stackIndex];
            if (el.name == fullName) {
                ListWrapper.splice(this.elementStack, stackIndex, this.elementStack.length - stackIndex);
                return true;
            }
            if (!getHtmlTagDefinition(el.name).closedByParent) {
                return false;
            }
        }
        return false;
    }
    _consumeAttr(attrName) {
        var fullName = mergeNsAndName(attrName.parts[0], attrName.parts[1]);
        var end = attrName.sourceSpan.end;
        var value = '';
        if (this.peek.type === HtmlTokenType.ATTR_VALUE) {
            var valueToken = this._advance();
            value = valueToken.parts[0];
            end = valueToken.sourceSpan.end;
        }
        return new HtmlAttrAst(fullName, value, new ParseSourceSpan(attrName.sourceSpan.start, end));
    }
    _getParentElement() {
        return this.elementStack.length > 0 ? ListWrapper.last(this.elementStack) : null;
    }
    _addToParent(node) {
        var parent = this._getParentElement();
        if (isPresent(parent)) {
            parent.children.push(node);
        }
        else {
            this.rootNodes.push(node);
        }
    }
}
function getElementFullName(prefix, localName, parentElement) {
    if (isBlank(prefix)) {
        prefix = getHtmlTagDefinition(localName).implicitNamespacePrefix;
        if (isBlank(prefix) && isPresent(parentElement)) {
            prefix = getNsPrefix(parentElement.name);
        }
    }
    return mergeNsAndName(prefix, localName);
}
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiaHRtbF9wYXJzZXIuanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJhbmd1bGFyMi9zcmMvY29tcGlsZXIvaHRtbF9wYXJzZXIudHMiXSwibmFtZXMiOlsiSHRtbFRyZWVFcnJvciIsIkh0bWxUcmVlRXJyb3IuY29uc3RydWN0b3IiLCJIdG1sVHJlZUVycm9yLmNyZWF0ZSIsIkh0bWxQYXJzZVRyZWVSZXN1bHQiLCJIdG1sUGFyc2VUcmVlUmVzdWx0LmNvbnN0cnVjdG9yIiwiSHRtbFBhcnNlciIsIkh0bWxQYXJzZXIucGFyc2UiLCJUcmVlQnVpbGRlciIsIlRyZWVCdWlsZGVyLmNvbnN0cnVjdG9yIiwiVHJlZUJ1aWxkZXIuYnVpbGQiLCJUcmVlQnVpbGRlci5fYWR2YW5jZSIsIlRyZWVCdWlsZGVyLl9hZHZhbmNlSWYiLCJUcmVlQnVpbGRlci5fY29uc3VtZUNkYXRhIiwiVHJlZUJ1aWxkZXIuX2NvbnN1bWVDb21tZW50IiwiVHJlZUJ1aWxkZXIuX2NvbnN1bWVUZXh0IiwiVHJlZUJ1aWxkZXIuX2Nsb3NlVm9pZEVsZW1lbnQiLCJUcmVlQnVpbGRlci5fY29uc3VtZVN0YXJ0VGFnIiwiVHJlZUJ1aWxkZXIuX3B1c2hFbGVtZW50IiwiVHJlZUJ1aWxkZXIuX2NvbnN1bWVFbmRUYWciLCJUcmVlQnVpbGRlci5fcG9wRWxlbWVudCIsIlRyZWVCdWlsZGVyLl9jb25zdW1lQXR0ciIsIlRyZWVCdWlsZGVyLl9nZXRQYXJlbnRFbGVtZW50IiwiVHJlZUJ1aWxkZXIuX2FkZFRvUGFyZW50IiwiZ2V0RWxlbWVudEZ1bGxOYW1lIl0sIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7T0FBTyxFQUNMLFNBQVMsRUFDVCxPQUFPLEVBT1IsTUFBTSwwQkFBMEI7T0FFMUIsRUFBQyxXQUFXLEVBQUMsTUFBTSxnQ0FBZ0M7T0FFbkQsRUFBVSxXQUFXLEVBQUUsV0FBVyxFQUFFLGNBQWMsRUFBRSxjQUFjLEVBQUMsTUFBTSxZQUFZO09BRXJGLEVBQUMsVUFBVSxFQUFDLE1BQU0sc0JB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LENBQUNBO0lBQ25GQSxDQUFDQTtJQUVPZCxZQUFZQSxDQUFDQSxJQUFhQTtRQUNoQ2UsSUFBSUEsTUFBTUEsR0FBR0EsSUFBSUEsQ0FBQ0EsaUJBQWlCQSxFQUFFQSxDQUFDQTtRQUN0Q0EsRUFBRUEsQ0FBQ0EsQ0FBQ0EsU0FBU0EsQ0FBQ0EsTUFBTUEsQ0FBQ0EsQ0FBQ0EsQ0FBQ0EsQ0FBQ0E7WUFDdEJBLE1BQU1BLENBQUNBLFFBQVFBLENBQUNBLElBQUlBLENBQUNBLElBQUlBLENBQUNBLENBQUNBO1FBQzdCQSxDQUFDQTtRQUFDQSxJQUFJQSxDQUFDQSxDQUFDQTtZQUNOQSxJQUFJQSxDQUFDQSxTQUFTQSxDQUFDQSxJQUFJQSxDQUFDQSxJQUFJQSxDQUFDQSxDQUFDQTtRQUM1QkEsQ0FBQ0E7SUFDSEEsQ0FBQ0E7QUFDSGYsQ0FBQ0E7QUFFRCw0QkFBNEIsTUFBYyxFQUFFLFNBQWlCLEVBQ2pDLGFBQTZCO0lBQ3ZEZ0IsRUFBRUEsQ0FBQ0EsQ0FBQ0EsT0FBT0EsQ0FBQ0EsTUFBTUEsQ0FBQ0EsQ0FBQ0EsQ0FBQ0EsQ0FBQ0E7UUFDcEJBLE1BQU1BLEdBQUdBLG9CQUFvQkEsQ0FBQ0EsU0FBU0EsQ0FBQ0EsQ0FBQ0EsdUJBQXVCQSxDQUFDQTtRQUNqRUEsRUFBRUEsQ0FBQ0EsQ0FBQ0EsT0FBT0EsQ0FBQ0EsTUFBTUEsQ0FBQ0EsSUFBSUEsU0FBU0EsQ0FBQ0EsYUFBYUEsQ0FBQ0EsQ0FBQ0EsQ0FBQ0EsQ0FBQ0E7WUFDaERBLE1BQU1BLEdBQUdBLFdBQVdBLENBQUNBLGFBQWFBLENBQUNBLElBQUlBLENBQUNBLENBQUNBO1FBQzNDQSxDQUFDQTtJQUNIQSxDQUFDQTtJQUVEQSxNQUFNQSxDQUFDQSxjQUFjQSxDQUFDQSxNQUFNQSxFQUFFQSxTQUFTQSxDQUFDQSxDQUFDQTtBQUMzQ0EsQ0FBQ0EiLCJzb3VyY2VzQ29udGVudCI6WyJpbXBvcnQge1xuICBpc1ByZXNlbnQsXG4gIGlzQmxhbmssXG4gIFN0cmluZ1dyYXBwZXIsXG4gIHN0cmluZ2lmeSxcbiAgYXNzZXJ0aW9uc0VuYWJsZWQsXG4gIFN0cmluZ0pvaW5lcixcbiAgc2VyaWFsaXplRW51bSxcbiAgQ09OU1RfRVhQUlxufSBmcm9tICdhbmd1bGFyMi9zcmMvZmFjYWRlL2xhbmcnO1xuXG5pbXBvcnQge0xpc3RXcmFwcGVyfSBmcm9tICdhbmd1bGFyMi9zcmMvZmFjYWRlL2NvbGxlY3Rpb24nO1xuXG5pbXBvcnQge0h0bWxBc3QsIEh0bWxBdHRyQXN0LCBIdG1sVGV4dEFzdCwgSHRtbENvbW1lbnRBc3QsIEh0bWxFbGVtZW50QXN0fSBmcm9tICcuL2h0bWxfYXN0JztcblxuaW1wb3J0IHtJbmplY3RhYmxlfSBmcm9tICdhbmd1bGFyMi9zcmMvY29yZS9kaSc7XG5pbXBvcnQge0h0bWxUb2tlbiwgSHRtbFRva2VuVHlwZSwgdG9rZW5pemVIdG1sfSBmcm9tICcuL2h0bWxfbGV4ZXInO1xuaW1wb3J0IHtQYXJzZUVycm9yLCBQYXJzZUxvY2F0aW9uLCBQYXJzZVNvdXJjZVNwYW59IGZyb20gJy4vcGFyc2VfdXRpbCc7XG5pbXBvcnQge0h0bWxUYWdEZWZpbml0aW9uLCBnZXRIdG1sVGFnRGVmaW5pdGlvbiwgZ2V0TnNQcmVmaXgsIG1lcmdlTnNBbmROYW1lfSBmcm9tICcuL2h0bWxfdGFncyc7XG5cbmV4cG9ydCBjbGFzcyBIdG1sVHJlZUVycm9yIGV4dGVuZHMgUGFyc2VFcnJvciB7XG4gIHN0YXRpYyBjcmVhdGUoZWxlbWVudE5hbWU6IHN0cmluZywgc3BhbjogUGFyc2VTb3VyY2VTcGFuLCBtc2c6IHN0cmluZyk6IEh0bWxUcmVlRXJyb3Ige1xuICAgIHJldHVybiBuZXcgSHRtbFRyZWVFcnJvcihlbGVtZW50TmFtZSwgc3BhbiwgbXNnKTtcbiAgfVxuXG4gIGNvbnN0cnVjdG9yKHB1YmxpYyBlbGVtZW50TmFtZTogc3RyaW5nLCBzcGFuOiBQYXJzZVNvdXJjZVNwYW4sIG1zZzogc3RyaW5nKSB7IHN1cGVyKHNwYW4sIG1zZyk7IH1cbn1cblxuZXhwb3J0IGNsYXNzIEh0bWxQYXJzZVRyZWVSZXN1bHQge1xuICBjb25zdHJ1Y3RvcihwdWJsaWMgcm9vdE5vZGVzOiBIdG1sQXN0W10sIHB1YmxpYyBlcnJvcnM6IFBhcnNlRXJyb3JbXSkge31cbn1cblxuQEluamVjdGFibGUoKVxuZXhwb3J0IGNsYXNzIEh0bWxQYXJzZXIge1xuICBwYXJzZShzb3VyY2VDb250ZW50OiBzdHJpbmcsIHNvdXJjZVVybDogc3RyaW5nKTogSHRtbFBhcnNlVHJlZVJlc3VsdCB7XG4gICAgdmFyIHRva2Vuc0FuZEVycm9ycyA9IHRva2VuaXplSHRtbChzb3VyY2VDb250ZW50LCBzb3VyY2VVcmwpO1xuICAgIHZhciB0cmVlQW5kRXJyb3JzID0gbmV3IFRyZWVCdWlsZGVyKHRva2Vuc0FuZEVycm9ycy50b2tlbnMpLmJ1aWxkKCk7XG4gICAgcmV0dXJuIG5ldyBIdG1sUGFyc2VUcmVlUmVzdWx0KHRyZWVBbmRFcnJvcnMucm9vdE5vZGVzLCAoPFBhcnNlRXJyb3JbXT50b2tlbnNBbmRFcnJvcnMuZXJyb3JzKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5jb25jYXQodHJlZUFuZEVycm9ycy5lcnJvcnMpKTtcbiAgfVxufVxuXG5jbGFzcyBUcmVlQnVpbGRlciB7XG4gIHByaXZhdGUgaW5kZXg6IG51bWJlciA9IC0xO1xuICBwcml2YXRlIHBlZWs6IEh0bWxUb2tlbjtcblxuICBwcml2YXRlIHJvb3ROb2RlczogSHRtbEFzdFtdID0gW107XG4gIHByaXZhdGUgZXJyb3JzOiBIdG1sVHJlZUVycm9yW10gPSBbXTtcblxuICBwcml2YXRlIGVsZW1lbnRTdGFjazogSHRtbEVsZW1lbnRBc3RbXSA9IFtdO1xuXG4gIGNvbnN0cnVjdG9yKHByaXZhdGUgdG9rZW5zOiBIdG1sVG9rZW5bXSkgeyB0aGlzLl9hZHZhbmNlKCk7IH1cblxuICBidWlsZCgpOiBIdG1sUGFyc2VUcmVlUmVzdWx0IHtcbiAgICB3aGlsZSAodGhpcy5wZWVrLnR5cGUgIT09IEh0bWxUb2tlblR5cGUuRU9GKSB7XG4gICAgICBpZiAodGhpcy5wZWVrLnR5cGUgPT09IEh0bWxUb2tlblR5cGUuVEFHX09QRU5fU1RBUlQpIHtcbiAgICAgICAgdGhpcy5fY29uc3VtZVN0YXJ0VGFnKHRoaXMuX2FkdmFuY2UoKSk7XG4gICAgICB9IGVsc2UgaWYgKHRoaXMucGVlay50eXBlID09PSBIdG1sVG9rZW5UeXBlLlRBR19DTE9TRSkge1xuICAgICAgICB0aGlzLl9jb25zdW1lRW5kVGFnKHRoaXMuX2FkdmFuY2UoKSk7XG4gICAgICB9IGVsc2UgaWYgKHRoaXMucGVlay50eXBlID09PSBIdG1sVG9rZW5UeXBlLkNEQVRBX1NUQVJUKSB7XG4gICAgICAgIHRoaXMuX2Nsb3NlVm9pZEVsZW1lbnQoKTtcbiAgICAgICAgdGhpcy5fY29uc3VtZUNkYXRhKHRoaXMuX2FkdmFuY2UoKSk7XG4gICAgICB9IGVsc2UgaWYgKHRoaXMucGVlay50eXBlID09PSBIdG1sVG9rZW5UeXBlLkNPTU1FTlRfU1RBUlQpIHtcbiAgICAgICAgdGhpcy5fY2xvc2VWb2lkRWxlbWVudCgpO1xuICAgICAgICB0aGlzLl9jb25zdW1lQ29tbWVudCh0aGlzLl9hZHZhbmNlKCkpO1xuICAgICAgfSBlbHNlIGlmICh0aGlzLnBlZWsudHlwZSA9PT0gSHRtbFRva2VuVHlwZS5URVhUIHx8XG4gICAgICAgICAgICAgICAgIHRoaXMucGVlay50eXBlID09PSBIdG1sVG9rZW5UeXBlLlJBV19URVhUIHx8XG4gICAgICAgICAgICAgICAgIHRoaXMucGVlay50eXBlID09PSBIdG1sVG9rZW5UeXBlLkVTQ0FQQUJMRV9SQVdfVEVYVCkge1xuICAgICAgICB0aGlzLl9jbG9zZVZvaWRFbGVtZW50KCk7XG4gICAgICAgIHRoaXMuX2NvbnN1bWVUZXh0KHRoaXMuX2FkdmFuY2UoKSk7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICAvLyBTa2lwIGFsbCBvdGhlciB0b2tlbnMuLi5cbiAgICAgICAgdGhpcy5fYWR2YW5jZSgpO1xuICAgICAgfVxuICAgIH1cbiAgICByZXR1cm4gbmV3IEh0bWxQYXJzZVRyZWVSZXN1bHQodGhpcy5yb290Tm9kZXMsIHRoaXMuZXJyb3JzKTtcbiAgfVxuXG4gIHByaXZhdGUgX2FkdmFuY2UoKTogSHRtbFRva2VuIHtcbiAgICB2YXIgcHJldiA9IHRoaXMucGVlaztcbiAgICBpZiAodGhpcy5pbmRleCA8IHRoaXMudG9rZW5zLmxlbmd0aCAtIDEpIHtcbiAgICAgIC8vIE5vdGU6IHRoZXJlIGlzIGFsd2F5cyBhbiBFT0YgdG9rZW4gYXQgdGhlIGVuZFxuICAgICAgdGhpcy5pbmRleCsrO1xuICAgIH1cbiAgICB0aGlzLnBlZWsgPSB0aGlzLnRva2Vuc1t0aGlzLmluZGV4XTtcbiAgICByZXR1cm4gcHJldjtcbiAgfVxuXG4gIHByaXZhdGUgX2FkdmFuY2VJZih0eXBlOiBIdG1sVG9rZW5UeXBlKTogSHRtbFRva2VuIHtcbiAgICBpZiAodGhpcy5wZWVrLnR5cGUgPT09IHR5cGUpIHtcbiAgICAgIHJldHVybiB0aGlzLl9hZHZhbmNlKCk7XG4gICAgfVxuICAgIHJldHVybiBudWxsO1xuICB9XG5cbiAgcHJpdmF0ZSBfY29uc3VtZUNkYXRhKHN0YXJ0VG9rZW46IEh0bWxUb2tlbikge1xuICAgIHRoaXMuX2NvbnN1bWVUZXh0KHRoaXMuX2FkdmFuY2UoKSk7XG4gICAgdGhpcy5fYWR2YW5jZUlmKEh0bWxUb2tlblR5cGUuQ0RBVEFfRU5EKTtcbiAgfVxuXG4gIHByaXZhdGUgX2NvbnN1bWVDb21tZW50KHRva2VuOiBIdG1sVG9rZW4pIHtcbiAgICB2YXIgdGV4dCA9IHRoaXMuX2FkdmFuY2VJZihIdG1sVG9rZW5UeXBlLlJBV19URVhUKTtcbiAgICB0aGlzLl9hZHZhbmNlSWYoSHRtbFRva2VuVHlwZS5DT01NRU5UX0VORCk7XG4gICAgdmFyIHZhbHVlID0gaXNQcmVzZW50KHRleHQpID8gdGV4dC5wYXJ0c1swXS50cmltKCkgOiBudWxsO1xuICAgIHRoaXMuX2FkZFRvUGFyZW50KG5ldyBIdG1sQ29tbWVudEFzdCh2YWx1ZSwgdG9rZW4uc291cmNlU3BhbikpO1xuICB9XG5cbiAgcHJpdmF0ZSBfY29uc3VtZVRleHQodG9rZW46IEh0bWxUb2tlbikge1xuICAgIGxldCB0ZXh0ID0gdG9rZW4ucGFydHNbMF07XG4gICAgaWYgKHRleHQubGVuZ3RoID4gMCAmJiB0ZXh0WzBdID09ICdcXG4nKSB7XG4gICAgICBsZXQgcGFyZW50ID0gdGhpcy5fZ2V0UGFyZW50RWxlbWVudCgpO1xuICAgICAgaWYgKGlzUHJlc2VudChwYXJlbnQpICYmIHBhcmVudC5jaGlsZHJlbi5sZW5ndGggPT0gMCAmJlxuICAgICAgICAgIGdldEh0bWxUYWdEZWZpbml0aW9uKHBhcmVudC5uYW1lKS5pZ25vcmVGaXJzdExmKSB7XG4gICAgICAgIHRleHQgPSB0ZXh0LnN1YnN0cmluZygxKTtcbiAgICAgIH1cbiAgICB9XG5cbiAgICBpZiAodGV4dC5sZW5ndGggPiAwKSB7XG4gICAgICB0aGlzLl9hZGRUb1BhcmVudChuZXcgSHRtbFRleHRBc3QodGV4dCwgdG9rZW4uc291cmNlU3BhbikpO1xuICAgIH1cbiAgfVxuXG4gIHByaXZhdGUgX2Nsb3NlVm9pZEVsZW1lbnQoKTogdm9pZCB7XG4gICAgaWYgKHRoaXMuZWxlbWVudFN0YWNrLmxlbmd0aCA+IDApIHtcbiAgICAgIGxldCBlbCA9IExpc3RXcmFwcGVyLmxhc3QodGhpcy5lbGVtZW50U3RhY2spO1xuXG4gICAgICBpZiAoZ2V0SHRtbFRhZ0RlZmluaXRpb24oZWwubmFtZSkuaXNWb2lkKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudFN0YWNrLnBvcCgpO1xuICAgICAgfVxuICAgIH1cbiAgfVxuXG4gIHByaXZhdGUgX2NvbnN1bWVTdGFydFRhZyhzdGFydFRhZ1Rva2VuOiBIdG1sVG9rZW4pIHtcbiAgICB2YXIgcHJlZml4ID0gc3RhcnRUYWdUb2tlbi5wYXJ0c1swXTtcbiAgICB2YXIgbmFtZSA9IHN0YXJ0VGFnVG9rZW4ucGFydHNbMV07XG4gICAgdmFyIGF0dHJzID0gW107XG4gICAgd2hpbGUgKHRoaXMucGVlay50eXBlID09PSBIdG1sVG9rZW5UeXBlLkFUVFJfTkFNRSkge1xuICAgICAgYXR0cnMucHVzaCh0aGlzLl9jb25zdW1lQXR0cih0aGlzLl9hZHZhbmNlKCkpKTtcbiAgICB9XG4gICAgdmFyIGZ1bGxOYW1lID0gZ2V0RWxlbWVudEZ1bGxOYW1lKHByZWZpeCwgbmFtZSwgdGhpcy5fZ2V0UGFyZW50RWxlbWVudCgpKTtcbiAgICB2YXIgc2VsZkNsb3NpbmcgPSBmYWxzZTtcbiAgICAvLyBOb3RlOiBUaGVyZSBjb3VsZCBoYXZlIGJlZW4gYSB0b2tlbml6ZXIgZXJyb3JcbiAgICAvLyBzbyB0aGF0IHdlIGRvbid0IGdldCBhIHRva2VuIGZvciB0aGUgZW5kIHRhZy4uLlxuICAgIGlmICh0aGlzLnBlZWsudHlwZSA9PT0gSHRtbFRva2VuVHlwZS5UQUdfT1BFTl9FTkRfVk9JRCkge1xuICAgICAgdGhpcy5fYWR2YW5jZSgpO1xuICAgICAgc2VsZkNsb3NpbmcgPSB0cnVlO1xuICAgICAgaWYgKGdldE5zUHJlZml4KGZ1bGxOYW1lKSA9PSBudWxsICYmICFnZXRIdG1sVGFnRGVmaW5pdGlvbihmdWxsTmFtZSkuaXNWb2lkKSB7XG4gICAgICAgIHRoaXMuZXJyb3JzLnB1c2goSHRtbFRyZWVFcnJvci5jcmVhdGUoXG4gICAgICAgICAgICBmdWxsTmFtZSwgc3RhcnRUYWdUb2tlbi5zb3VyY2VTcGFuLFxuICAgICAgICAgICAgYE9ubHkgdm9pZCBhbmQgZm9yZWlnbiBlbGVtZW50cyBjYW4gYmUgc2VsZiBjbG9zZWQgXCIke3N0YXJ0VGFnVG9rZW4ucGFydHNbMV19XCJgKSk7XG4gICAgICB9XG4gICAgfSBlbHNlIGlmICh0aGlzLnBlZWsudHlwZSA9PT0gSHRtbFRva2VuVHlwZS5UQUdfT1BFTl9FTkQpIHtcbiAgICAgIHRoaXMuX2FkdmFuY2UoKTtcbiAgICAgIHNlbGZDbG9zaW5nID0gZmFsc2U7XG4gICAgfVxuICAgIHZhciBlbmQgPSB0aGlzLnBlZWsuc291cmNlU3Bhbi5zdGFydDtcbiAgICB2YXIgZWwgPSBuZXcgSHRtbEVsZW1lbnRBc3QoZnVsbE5hbWUsIGF0dHJzLCBbXSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgbmV3IFBhcnNlU291cmNlU3BhbihzdGFydFRhZ1Rva2VuLnNvdXJjZVNwYW4uc3RhcnQsIGVuZCkpO1xuICAgIHRoaXMuX3B1c2hFbGVtZW50KGVsKTtcbiAgICBpZiAoc2VsZkNsb3NpbmcpIHtcbiAgICAgIHRoaXMuX3BvcEVsZW1lbnQoZnVsbE5hbWUpO1xuICAgIH1cbiAgfVxuXG4gIHByaXZhdGUgX3B1c2hFbGVtZW50KGVsOiBIdG1sRWxlbWVudEFzdCkge1xuICAgIGlmICh0aGlzLmVsZW1lbnRTdGFjay5sZW5ndGggPiAwKSB7XG4gICAgICB2YXIgcGFyZW50RWwgPSBMaXN0V3JhcHBlci5sYXN0KHRoaXMuZWxlbWVudFN0YWNrKTtcbiAgICAgIGlmIChnZXRIdG1sVGFnRGVmaW5pdGlvbihwYXJlbnRFbC5uYW1lKS5pc0Nsb3NlZEJ5Q2hpbGQoZWwubmFtZSkpIHtcbiAgICAgICAgdGhpcy5lbGVtZW50U3RhY2sucG9wKCk7XG4gICAgICB9XG4gICAgfVxuXG4gICAgdmFyIHRhZ0RlZiA9IGdldEh0bWxUYWdEZWZpbml0aW9uKGVsLm5hbWUpO1xuICAgIHZhciBwYXJlbnRFbCA9IHRoaXMuX2dldFBhcmVudEVsZW1lbnQoKTtcbiAgICBpZiAodGFnRGVmLnJlcXVpcmVFeHRyYVBhcmVudChpc1ByZXNlbnQocGFyZW50RWwpID8gcGFyZW50RWwubmFtZSA6IG51bGwpKSB7XG4gICAgICB2YXIgbmV3UGFyZW50ID0gbmV3IEh0bWxFbGVtZW50QXN0KHRhZ0RlZi5wYXJlbnRUb0FkZCwgW10sIFtlbF0sIGVsLnNvdXJjZVNwYW4pO1xuICAgICAgdGhpcy5fYWRkVG9QYXJlbnQobmV3UGFyZW50KTtcbiAgICAgIHRoaXMuZWxlbWVudFN0YWNrLnB1c2gobmV3UGFyZW50KTtcbiAgICAgIHRoaXMuZWxlbWVudFN0YWNrLnB1c2goZWwpO1xuICAgIH0gZWxzZSB7XG4gICAgICB0aGlzLl9hZGRUb1BhcmVudChlbCk7XG4gICAgICB0aGlzLmVsZW1lbnRTdGFjay5wdXNoKGVsKTtcbiAgICB9XG4gIH1cblxuICBwcml2YXRlIF9jb25zdW1lRW5kVGFnKGVuZFRhZ1Rva2VuOiBIdG1sVG9rZW4pIHtcbiAgICB2YXIgZnVsbE5hbWUgPVxuICAgICAgICBnZXRFbGVtZW50RnVsbE5hbWUoZW5kVGFnVG9rZW4ucGFydHNbMF0sIGVuZFRhZ1Rva2VuLnBhcnRzWzFdLCB0aGlzLl9nZXRQYXJlbnRFbGVtZW50KCkpO1xuXG4gICAgaWYgKGdldEh0bWxUYWdEZWZpbml0aW9uKGZ1bGxOYW1lKS5pc1ZvaWQpIHtcbiAgICAgIHRoaXMuZXJyb3JzLnB1c2goXG4gICAgICAgICAgSHRtbFRyZWVFcnJvci5jcmVhdGUoZnVsbE5hbWUsIGVuZFRhZ1Rva2VuLnNvdXJjZVNwYW4sXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYFZvaWQgZWxlbWVudHMgZG8gbm90IGhhdmUgZW5kIHRhZ3MgXCIke2VuZFRhZ1Rva2VuLnBhcnRzWzFdfVwiYCkpO1xuICAgIH0gZWxzZSBpZiAoIXRoaXMuX3BvcEVsZW1lbnQoZnVsbE5hbWUpKSB7XG4gICAgICB0aGlzLmVycm9ycy5wdXNoKEh0bWxUcmVlRXJyb3IuY3JlYXRlKGZ1bGxOYW1lLCBlbmRUYWdUb2tlbi5zb3VyY2VTcGFuLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBgVW5leHBlY3RlZCBjbG9zaW5nIHRhZyBcIiR7ZW5kVGFnVG9rZW4ucGFydHNbMV19XCJgKSk7XG4gICAgfVxuICB9XG5cbiAgcHJpdmF0ZSBfcG9wRWxlbWVudChmdWxsTmFtZTogc3RyaW5nKTogYm9vbGVhbiB7XG4gICAgZm9yIChsZXQgc3RhY2tJbmRleCA9IHRoaXMuZWxlbWVudFN0YWNrLmxlbmd0aCAtIDE7IHN0YWNrSW5kZXggPj0gMDsgc3RhY2tJbmRleC0tKSB7XG4gICAgICBsZXQgZWwgPSB0aGlzLmVsZW1lbnRTdGFja1tzdGFja0luZGV4XTtcbiAgICAgIGlmIChlbC5uYW1lID09IGZ1bGxOYW1lKSB7XG4gICAgICAgIExpc3RXcmFwcGVyLnNwbGljZSh0aGlzLmVsZW1lbnRTdGFjaywgc3RhY2tJbmRleCwgdGhpcy5lbGVtZW50U3RhY2subGVuZ3RoIC0gc3RhY2tJbmRleCk7XG4gICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgfVxuXG4gICAgICBpZiAoIWdldEh0bWxUYWdEZWZpbml0aW9uKGVsLm5hbWUpLmNsb3NlZEJ5UGFyZW50KSB7XG4gICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgIH1cbiAgICB9XG4gICAgcmV0dXJuIGZhbHNlO1xuICB9XG5cbiAgcHJpdmF0ZSBfY29uc3VtZUF0dHIoYXR0ck5hbWU6IEh0bWxUb2tlbik6IEh0bWxBdHRyQXN0IHtcbiAgICB2YXIgZnVsbE5hbWUgPSBtZXJnZU5zQW5kTmFtZShhdHRyTmFtZS5wYXJ0c1swXSwgYXR0ck5hbWUucGFydHNbMV0pO1xuICAgIHZhciBlbmQgPSBhdHRyTmFtZS5zb3VyY2VTcGFuLmVuZDtcbiAgICB2YXIgdmFsdWUgPSAnJztcbiAgICBpZiAodGhpcy5wZWVrLnR5cGUgPT09IEh0bWxUb2tlblR5cGUuQVRUUl9WQUxVRSkge1xuICAgICAgdmFyIHZhbHVlVG9rZW4gPSB0aGlzLl9hZHZhbmNlKCk7XG4gICAgICB2YWx1ZSA9IHZhbHVlVG9rZW4ucGFydHNbMF07XG4gICAgICBlbmQgPSB2YWx1ZVRva2VuLnNvdXJjZVNwYW4uZW5kO1xuICAgIH1cbiAgICByZXR1cm4gbmV3IEh0bWxBdHRyQXN0KGZ1bGxOYW1lLCB2YWx1ZSwgbmV3IFBhcnNlU291cmNlU3BhbihhdHRyTmFtZS5zb3VyY2VTcGFuLnN0YXJ0LCBlbmQpKTtcbiAgfVxuXG4gIHByaXZhdGUgX2dldFBhcmVudEVsZW1lbnQoKTogSHRtbEVsZW1lbnRBc3Qge1xuICAgIHJldHVybiB0aGlzLmVsZW1lbnRTdGFjay5sZW5ndGggPiAwID8gTGlzdFdyYXBwZXIubGFzdCh0aGlzLmVsZW1lbnRTdGFjaykgOiBudWxsO1xuICB9XG5cbiAgcHJpdmF0ZSBfYWRkVG9QYXJlbnQobm9kZTogSHRtbEFzdCkge1xuICAgIHZhciBwYXJlbnQgPSB0aGlzLl9nZXRQYXJlbnRFbGVtZW50KCk7XG4gICAgaWYgKGlzUHJlc2VudChwYXJlbnQpKSB7XG4gICAgICBwYXJlbnQuY2hpbGRyZW4ucHVzaChub2RlKTtcbiAgICB9IGVsc2Uge1xuICAgICAgdGhpcy5yb290Tm9kZXMucHVzaChub2RlKTtcbiAgICB9XG4gIH1cbn1cblxuZnVuY3Rpb24gZ2V0RWxlbWVudEZ1bGxOYW1lKHByZWZpeDogc3RyaW5nLCBsb2NhbE5hbWU6IHN0cmluZyxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBwYXJlbnRFbGVtZW50OiBIdG1sRWxlbWVudEFzdCk6IHN0cmluZyB7XG4gIGlmIChpc0JsYW5rKHByZWZpeCkpIHtcbiAgICBwcmVmaXggPSBnZXRIdG1sVGFnRGVmaW5pdGlvbihsb2NhbE5hbWUpLmltcGxpY2l0TmFtZXNwYWNlUHJlZml4O1xuICAgIGlmIChpc0JsYW5rKHByZWZpeCkgJiYgaXNQcmVzZW50KHBhcmVudEVsZW1lbnQpKSB7XG4gICAgICBwcmVmaXggPSBnZXROc1ByZWZpeChwYXJlbnRFbGVtZW50Lm5hbWUpO1xuICAgIH1cbiAgfVxuXG4gIHJldHVybiBtZXJnZU5zQW5kTmFtZShwcmVmaXgsIGxvY2FsTmFtZSk7XG59XG4iXX0=