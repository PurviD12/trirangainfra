import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { InquiryAddAttachmentComponent } from './inquiry-add-attachment.component';

describe('InquiryAddAttachmentComponent', () => {
  let component: InquiryAddAttachmentComponent;
  let fixture: ComponentFixture<InquiryAddAttachmentComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ InquiryAddAttachmentComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(InquiryAddAttachmentComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
